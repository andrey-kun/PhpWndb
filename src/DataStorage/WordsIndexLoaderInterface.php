<?php
declare(strict_types=1);

namespace AL\PhpWndb\DataStorage;

use AL\PhpWndb\Exceptions\IOException;

interface WordsIndexLoaderInterface
{
	/**
	 * @throws IOException
	 */
	public function findLemmaIndexData(string $lemma): ?string;
}

