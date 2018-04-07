<?php
declare(strict_types=1);

namespace AL\PhpWndb\Model\Synsets;

use AL\PhpEnum\Enum;
use AL\PhpWndb\DataMapping\SynsetDataMapperInterface;
use AL\PhpWndb\Model\Exceptions\SynsetCreateException;
use AL\PhpWndb\Model\Relations\RelationPointerFactoryInterface;
use AL\PhpWndb\Model\Relations\RelationsFactoryInterface;
use AL\PhpWndb\Model\Relations\RelationsInterface;
use AL\PhpWndb\Model\Synsets\Adjectives\SynsetAdjectives;
use AL\PhpWndb\Model\Synsets\Adverbs\SynsetAdverbs;
use AL\PhpWndb\Model\Synsets\Nouns\SynsetNouns;
use AL\PhpWndb\Model\Synsets\Verbs\SynsetVerbs;
use AL\PhpWndb\Model\Words\WordFactoryInterface;
use AL\PhpWndb\Model\Words\WordInterface;
use AL\PhpWndb\Parsing\ParsedData\ParsedFrameDataInterface;
use AL\PhpWndb\Parsing\ParsedData\ParsedPointerDataInterface;
use AL\PhpWndb\Parsing\ParsedData\ParsedSynsetDataInterface;
use AL\PhpWndb\Parsing\ParsedData\ParsedWordDataInterface;
use AL\PhpWndb\PartOfSpeechEnum;
use UnexpectedValueException;
use InvalidArgumentException;
use OutOfRangeException;

class SynsetFactory implements SynsetFactoryInterface
{
	/** @var SynsetDataMapperInterface */
	private $synsetDataMapper;

	/** @var RelationsFactoryInterface */
	private $relationsFactory;

	/** @var RelationPointerFactoryInterface */
	private $relationPointerFactory;

	/** @var WordFactoryInterface */
	private $wordFactory;


	public function __construct(
		SynsetDataMapperInterface $synsetDataMapper,
		RelationsFactoryInterface $relationsFactory,
		RelationPointerFactoryInterface $relationPointerFactory,
		WordFactoryInterface $wordFactory
	) {
		$this->synsetDataMapper = $synsetDataMapper;
		$this->relationsFactory = $relationsFactory;
		$this->relationPointerFactory = $relationPointerFactory;
		$this->wordFactory = $wordFactory;
	}


	public function createSynsetFromParseData(ParsedSynsetDataInterface $parsedSynsetData): SynsetInterface
	{
		try {
			$wordsData = $parsedSynsetData->getWords();
			$synsetOffset = $parsedSynsetData->getSynsetOffset();
			$gloss = $parsedSynsetData->getGloss();

			$partOfSpeech = $this->synsetDataMapper->mapPartOfSpeech($parsedSynsetData->getPartOfSpeech());
			$synsetCategory = $this->mapSynsetCategory($partOfSpeech, $parsedSynsetData->getLexFileNumber());
			
			$pointers = $this->createPointers($partOfSpeech, $parsedSynsetData->getPointers(), count($wordsData));
			$frames = $this->createFrames($partOfSpeech, $parsedSynsetData->getFrames(), count($wordsData));
			$words = $this->createWords($partOfSpeech, $wordsData, $pointers, $frames);

			return $this->createSynset($partOfSpeech, $synsetOffset, $gloss, $words, $synsetCategory);
		}
		catch (\Throwable $e) {
			throw new SynsetCreateException('Create synset failed: ' . $e->getMessage(), 0, $e);
		}
	}


	/**
	 * @throws UnexpectedValueException
	 */
	protected function createSynset(PartOfSpeechEnum $partOfSpeech, int $synsetOffset, string $gloss, iterable $words, Enum $synsetCategory): SynsetInterface
	{
		switch ($partOfSpeech) {
			case PartOfSpeechEnum::ADJECTIVE(): return new SynsetAdjectives($synsetOffset, $gloss, $words, $synsetCategory);
			case PartOfSpeechEnum::ADVERB():    return new SynsetAdverbs($synsetOffset, $gloss, $words, $synsetCategory);
			case PartOfSpeechEnum::NOUN():      return new SynsetNouns($synsetOffset, $gloss, $words, $synsetCategory);
			case PartOfSpeechEnum::VERB():      return new SynsetVerbs($synsetOffset, $gloss, $words, $synsetCategory);
			default: throw new UnexpectedValueException("Unexpected part of speech: $partOfSpeech");
		}
	}

	/**
	 * @throws UnexpectedValueException
	 */
	protected function mapSynsetCategory(PartOfSpeechEnum $partOfSpeech, int $synsetCategoryData): Enum
	{
		switch ($partOfSpeech) {
			case PartOfSpeechEnum::ADJECTIVE(): return $this->synsetDataMapper->mapSynsetAdjectivesCategory($synsetCategoryData);
			case PartOfSpeechEnum::ADVERB():    return $this->synsetDataMapper->mapSynsetAdverbsCategory($synsetCategoryData);
			case PartOfSpeechEnum::NOUN():      return $this->synsetDataMapper->mapSynsetNounsCategory($synsetCategoryData);
			case PartOfSpeechEnum::VERB():      return $this->synsetDataMapper->mapSynsetVerbsCategory($synsetCategoryData);
			default: throw new UnexpectedValueException("Unexpected part of speech: $partOfSpeech");
		}
	}

	/**
	 * @param ParsedPointerDataInterface[] $pointersData
	 */
	protected function createPointers(PartOfSpeechEnum $sourcePartOfSpeech, iterable $pointersData, int $wordsCount): ArraysHolder
	{
		$pointers = new ArraysHolder($wordsCount);
		foreach ($pointersData as $pointerData) {
			$pointerType = $this->synsetDataMapper->mapRelationPointerType($pointerData->getPointerType(), $sourcePartOfSpeech);
			$targetPartOfSpeech = $this->synsetDataMapper->mapPartOfSpeech($pointerData->getPartOfSpeech());

			$pointer = $this->relationPointerFactory->createRelationPointer(
				$pointerType,
				$targetPartOfSpeech,
				$pointerData->getSynsetOffset(),
				$pointerData->getTargetWordIndex() ?: null
			);
			$index = $pointerData->getSourceWordIndex() - 1;

			$pointers->add($index, $pointer);
		}

		return $pointers;
	}

	/**
	 * @param ParsedFrameDataInterface[] $framesData
	 */
	protected function createFrames(PartOfSpeechEnum $sourcePartOfSpeech, iterable $framesData, int $wordsCount): ArraysHolder
	{
		if ($sourcePartOfSpeech !== PartOfSpeechEnum::VERB() && !empty($framesData)) {
			throw new InvalidArgumentException("There should not be any frames for part of speech $sourcePartOfSpeech.");
		}

		$frames = new ArraysHolder($wordsCount);
		foreach ($framesData as $frameData) {
			$frames->add(
				$frameData->getWordIndex() - 1,
				$frameData->getFrameNumber()
			);
		}

		return $frames;
	}

	/**
	 * @param ParsedWordDataInterface[] $wordsData
	 * @return WordInterface[]
	 */
	protected function createWords(PartOfSpeechEnum $partOfSpeech, iterable $wordsData, ArraysHolder $pointers, ArraysHolder $frames): iterable
	{
		$words = [];
		foreach ($wordsData as $key => $wordData) {
			$relations = $this->relationsFactory->createRelations($pointers->get($key));
			$words[] = $this->createWord($partOfSpeech, $wordData, $relations, $frames->get($key));
		}

		return $words;
	}

	/**
	 * @param int[] $frames
	 */
	protected function createWord(PartOfSpeechEnum $partOfSpeech, ParsedWordDataInterface $wordData, RelationsInterface $relations, array $frames): WordInterface
	{
		switch ($partOfSpeech) {
			case PartOfSpeechEnum::ADJECTIVE(): return $this->wordFactory->createAdjective($wordData->getValue(), $wordData->getLexId(), $relations);
			case PartOfSpeechEnum::ADVERB():    return $this->wordFactory->createAdverb($wordData->getValue(), $wordData->getLexId(), $relations);
			case PartOfSpeechEnum::NOUN():      return $this->wordFactory->createNoun($wordData->getValue(), $wordData->getLexId(), $relations);
			case PartOfSpeechEnum::VERB():      return $this->wordFactory->createVerb($wordData->getValue(), $wordData->getLexId(), $relations, $frames);
			default: throw new InvalidArgumentException("Unknown part of speech: $partOfSpeech");
		}
	}
}


/**
 * @internal
 */
class ArraysHolder
{
	/** @var array */
	private $data = [];

	/** @var int */
	private $count;


	public function __construct(int $count)
	{
		$this->count = $count;
	}


	/**
	 * @throws OutOfRangeException
	 */
	public function add(int $index, $value): void
	{
		if ($index >= $this->count) {
			throw new OutOfRangeException("Index ($index) has to be less than {$this->count}.");
		}

		if ($index < 0) {
			$this->addToAll($value);
		}
		else {
			$this->data[$index][] = $value;
		}
	}

	public function get(int $index): array
	{
		return $this->data[$index] ?? [];
	}


	private function addToAll($value): void
	{
		for ($i = 0; $i < $this->count; ++$i) {
			$this->data[$i][] = $value;
		}
	}
}