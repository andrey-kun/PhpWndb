<?php
declare(strict_types = 1);

namespace AL\PhpWndb\Tests\DataStorage;

use AL\PhpWndb\DataStorage\FileReaderInterface;
use AL\PhpWndb\DataStorage\WholeSynsetDataLoader;
use AL\PhpWndb\Tests\BaseTestAbstract;

class WholeSynsetDataLoaderTest extends BaseTestAbstract
{
	public function testGetSynsetData(): void
	{
		$loader = $this->createLoader([
			'  1 This line is ignored',
			'  2 and this too',
			'00000030 03 n 01 entity 0 003 ~ 00001930 n 0000 ~ 00002137 n 0000 ~ 04431553 n 0000 | that which is perceived or known or inferred to have its own distinct existence (living or nonliving)',
			'00000218 03 n 01 physical_entity 0 007 @ 00001740 n 0000 ~ 00002452 n 0000 ~ 00002684 n 0000 ~ 00007347 n 0000 ~ 00021007 n 0000 ~ 00029976 n 0000 ~ 14604577 n 0000 | an entity that has physical existence',
		]);

		static::assertSame(
			'00000030 03 n 01 entity 0 003 ~ 00001930 n 0000 ~ 00002137 n 0000 ~ 04431553 n 0000 | that which is perceived or known or inferred to have its own distinct existence (living or nonliving)',
			$loader->getSynsetData(30)
		);

		static::assertSame(
			'00000218 03 n 01 physical_entity 0 007 @ 00001740 n 0000 ~ 00002452 n 0000 ~ 00002684 n 0000 ~ 00007347 n 0000 ~ 00021007 n 0000 ~ 00029976 n 0000 ~ 14604577 n 0000 | an entity that has physical existence',
			$loader->getSynsetData(218)
		);
	}

	/**
	 * @expectedException \AL\PhpWndb\Exceptions\UnknownSynsetOffsetException
	 */
	public function testGetSynsetDataUnknowSynsetOffset(): void
	{
		$loader = $this->createLoader([
			'00000218 03 n 01 physical_entity 0 007 @ 00001740 n 0000 ~ 00002452 n 0000 ~ 00002684 n 0000 ~ 00007347 n 0000 ~ 00021007 n 0000 ~ 00029976 n 0000 ~ 14604577 n 0000 | an entity that has physical existence',
		]);
		$loader->getSynsetData(1000);
	}

	/**
	 * @expectedException \AL\PhpWndb\Exceptions\InvalidStateException
	 */
	public function testGetSynsetDataDuplicitOffset(): void
	{
		$loader = $this->createLoader([
			'00000030 03 n 01 entity 0 003 ~ 00001930 n 0000 ~ 00002137 n 0000 ~ 04431553 n 0000 | that which is perceived or known or inferred to have its own distinct existence (living or nonliving)',
			'00000030 03 n 01 physical_entity 0 007 @ 00001740 n 0000 ~ 00002452 n 0000 ~ 00002684 n 0000 ~ 00007347 n 0000 ~ 00021007 n 0000 ~ 00029976 n 0000 ~ 14604577 n 0000 | an entity that has physical existence',
		]);
		$loader->getSynsetData(30);
	}


	private function createLoader(array $data): WholeSynsetDataLoader
	{
		$loader = $this->createMock(FileReaderInterface::class);
		$loader->method('readAll')->willReturn($data);

		return new WholeSynsetDataLoader($loader);
	}
}
