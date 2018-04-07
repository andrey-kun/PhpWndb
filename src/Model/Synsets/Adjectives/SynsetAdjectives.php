<?php
declare(strict_types=1);

namespace AL\PhpWndb\Model\Synsets\Adjectives;

use AL\PhpWndb\Model\Synsets\SynsetAbstract;
use AL\PhpWndb\Model\Words\AdjectiveInterface;

class SynsetAdjectives extends SynsetAbstract implements SynsetAdjectivesInterface
{
	/** @var SynsetAdjectivesCategoryEnum */
	protected $synsetCategory;


	/**
	 * @param AdjectiveInterface[] $words
	 */
	public function __construct(int $synsetOffset, string $gloss, iterable $words, SynsetAdjectivesCategoryEnum $synsetCategory)
	{
		parent::__construct($synsetOffset, $gloss, $words);
		$this->synsetCategory = $synsetCategory;
	}


	public function getAdjectives(): iterable
	{
		return $this->words;
	}

	public function getSynsetCategory(): SynsetAdjectivesCategoryEnum
	{
		return $this->synsetCategory;
	}
}
