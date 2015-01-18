<?php
namespace FluidTYPO3\Flux\Tests\Unit\Provider;

/*
 * This file is part of the FluidTYPO3/Flux project under GPLv2 or later.
 *
 * For the full copyright and license information, please read the
 * LICENSE.md file that was distributed with this source code.
 */

use FluidTYPO3\Flux\Provider\ContentProvider;
use FluidTYPO3\Flux\Tests\Fixtures\Data\Records;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;

/**
 * @package Flux
 */
class ContentProviderTest extends AbstractProviderTest {

	/**
	 * @test
	 */
	public function triggersContentManipulatorOnDatabaseOperationNew() {
		$row = Records::$contentRecordWithoutParentAndWithoutChildren;
		$provider = $this->getConfigurationProviderInstance();
		/** @var DataHandler $tceMain */
		$tceMain = GeneralUtility::makeInstance('TYPO3\CMS\Core\DataHandling\DataHandler');
		$provider->postProcessDatabaseOperation('new', $row['uid'], $row, $tceMain);
	}

	/**
	 * @test
	 */
	public function triggersContentManipulatorOnPasteCommandWithCallbackInUrl() {
		$_GET['CB'] = array('paste' => 'tt_content|0');
		$row = Records::$contentRecordWithoutParentAndWithoutChildren;
		$provider = $this->getConfigurationProviderInstance();
		/** @var DataHandler $tceMain */
		$tceMain = GeneralUtility::makeInstance('TYPO3\CMS\Core\DataHandling\DataHandler');
		$relativeUid = 0;
		$contentService = $this->getMock('FluidTYPO3\Flux\Service\ContentService', array('updateRecordInDatabase'));
		$contentService->expects($this->once())->method('updateRecordInDatabase');
		ObjectAccess::setProperty($provider, 'contentService', $contentService, TRUE);
		$provider->postProcessCommand('move', 0, $row, $relativeUid, $tceMain);
	}

	/**
	 * @test
	 */
	public function canGetExtensionKey() {
		$provider = $this->getConfigurationProviderInstance();
		$record = $this->getBasicRecord();
		$extensionKey = $provider->getExtensionKey($record);
		$this->assertSame('flux', $extensionKey);
	}

	/**
	 * @test
	 */
	public function canGetTableName() {
		$provider = $this->getConfigurationProviderInstance();
		$record = $this->getBasicRecord();
		$tableName = $provider->getTableName($record);
		$this->assertSame('tt_content', $tableName);
	}

	/**
	 * @test
	 */
	public function canGetCallbackCommand() {
		$instance = $this->createInstance();
		$command = $this->callInaccessibleMethod($instance, 'getCallbackCommand');
		$this->assertIsArray($command);
	}

	/**
	 * @test
	 */
	public function postProcessCommandCallsExpectedMethodToMoveRecord() {
		$mock = $this->getMock(str_replace('Tests\\Unit\\', '', substr(get_class($this), 0, -4)), array('getCallbackCommand'));
		$mock->expects($this->once())->method('getCallbackCommand')->will($this->returnValue(array('move' => 1)));
		$mockContentService = $this->getMock('FluidTYPO3\Flux\Service\ContentService', array('pasteAfter', 'moveRecord'));
		$mockContentService->expects($this->once())->method('moveRecord');
		ObjectAccess::setProperty($mock, 'contentService', $mockContentService, TRUE);
		$command = 'move';
		$id = 0;
		$record = $this->getBasicRecord();
		$relativeTo = 0;
		$reference = new DataHandler();
		$mock->reset();
		$mock->postProcessCommand($command, $id, $record, $relativeTo, $reference);
	}

	/**
	 * @test
	 */
	public function postProcessCommandCallsExpectedMethodToCopyRecord() {
		$record = $this->getBasicRecord();
		$mock = $this->getMock(str_replace('Tests\\Unit\\', '', substr(get_class($this), 0, -4)), array('getCallbackCommand'));
		$mock->expects($this->once())->method('getCallbackCommand')->will($this->returnValue(array('paste' => 1)));
		$mockContentService = $this->getMock('FluidTYPO3\Flux\Service\ContentService', array('pasteAfter', 'moveRecord'));
		$mockContentService->expects($this->once())->method('pasteAfter');
		ObjectAccess::setProperty($mock, 'contentService', $mockContentService, TRUE);
		$command = 'copy';
		$id = 0;
		$relativeTo = 0;
		$reference = new DataHandler();
		$mock->reset();
		$mock->postProcessCommand($command, $id, $record, $relativeTo, $reference);
	}

	/**
	 * @test
	 */
	public function postProcessCommandCallsExpectedMethodToPasteRecord() {
		$record = $this->getBasicRecord();
		$mock = $this->getMock(str_replace('Tests\\Unit\\', '', substr(get_class($this), 0, -4)), array('getCallbackCommand'));
		$mock->expects($this->once())->method('getCallbackCommand')->will($this->returnValue(array('paste' => 1)));
		$mockContentService = $this->getMock('FluidTYPO3\Flux\Service\ContentService', array('pasteAfter', 'moveRecord'));
		$mockContentService->expects($this->once())->method('pasteAfter');
		ObjectAccess::setProperty($mock, 'contentService', $mockContentService, TRUE);
		$command = 'move';
		$id = 0;
		$relativeTo = 0;
		$reference = new DataHandler();
		$mock->reset();
		$mock->postProcessCommand($command, $id, $record, $relativeTo, $reference);
	}

	/**
	 * @test
	 */
	public function postProcessDatabaseOperationWithStatusNewCallsInitializeRecord() {
		$contentService = $this->getMock('FluidTYPO3\\Flux\\Service\\ContentService', array('initializeRecord'));
		$contentService->expects($this->once())->method('initializeRecord');
		/** @var DataHandler $tceMain */
		$tceMain = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
		$row = array();
		$mock = $this->objectManager->get($this->createInstanceClassName());
		$mock->reset();
		ObjectAccess::setProperty($mock, 'contentService', $contentService, TRUE);
		$mock->postProcessDatabaseOperation('new', 1, $row, $tceMain);
	}

	/**
	 * @test
	 * @dataProvider getPriorityTestValues
	 * @param array $row
	 * @param integer $expectedPriority
	 */
	public function testGetPriority(array $row, $expectedPriority) {
		$provider = $this->objectManager->get($this->createInstanceClassName());
		$priority = $provider->getPriority($row);
		$this->assertEquals($expectedPriority, $priority);
	}

	/**
	 * @return array
	 */
	public function getPriorityTestValues() {
		return array(
			array(array('CType' => 'anyotherctype', 'list_type' => ''), 50),
			array(array('CType' => 'anyotherctype', 'list_type' => 'withlisttype'), 0),
		);
	}

	/**
	 * @test
	 * @dataProvider getTriggerTestValues
	 * @param array $row
	 * @param string $table
	 * @param string $field
	 * @param string $extensionKey
	 * @param boolean $expectedResult
	 */
	public function testTrigger($row, $table, $field, $extensionKey, $expectedResult) {
		$provider = $this->objectManager->get($this->createInstanceClassName());
		$result = $provider->trigger($row, $table, $field, $extensionKey);
		$this->assertEquals($expectedResult, $result);
	}

	/**
	 * @return array
	 */
	public function getTriggerTestValues() {
		return array(
			array(array(), 'not_tt_content', 'pi_flexform', NULL, FALSE),
			array(array('list_type' => 'any', 'CType' => 'any'), 'not_tt_content', 'pi_flexform', NULL, FALSE),
			array(array('list_type' => 'any', 'CType' => 'any'), 'not_tt_content', 'pi_flexform', 'flux', FALSE),
			// triggers on record having list_type and CType, default field name and any combo of ext key
			array(array('list_type' => 'any', 'CType' => 'any'), 'tt_content', 'pi_flexform', NULL, TRUE),
			array(array('list_type' => 'any', 'CType' => 'any'), 'tt_content', 'pi_flexform', 'fluidpages', TRUE),
			array(array('list_type' => 'any', 'CType' => 'any'), 'tt_content', 'pi_flexform', 'flux', TRUE),
		);
	}

}
