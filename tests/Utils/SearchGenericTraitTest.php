<?php

namespace RytoEX\OBS\LogAnalyzer\Tests\Utils;

class SearchGenericTraitTest extends \PHPUnit\Framework\TestCase
{
	public function testFindString()
	{
		$mock = $this->getMockForTrait(\RytoEX\OBS\LogAnalyzer\Utils\SearchGenericTrait::class);

		$this->assertEquals($mock->find_string('<span>test html</span>',
			'<span>', '</span>'),
			'test html');
	}
}
