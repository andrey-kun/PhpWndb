<?php
declare(strict_types=1);

namespace AL\PhpWndb\Model\Relations;

class RelationsFactory implements RelationsFactoryInterface
{
	public function createRelations(iterable $relationPointers): RelationsInterface
	{
		$relations = new Relations();
		$relations->addRelationPointers($relationPointers);
		return $relations;
	}
}