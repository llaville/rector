<?php

declare (strict_types=1);
namespace RectorPrefix20220606;

use RectorPrefix20220606\Rector\Config\RectorConfig;
use RectorPrefix20220606\Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use RectorPrefix20220606\Rector\Renaming\ValueObject\MethodCallRename;
use RectorPrefix20220606\Rector\Transform\Rector\Assign\PropertyFetchToMethodCallRector;
use RectorPrefix20220606\Rector\Transform\ValueObject\PropertyFetchToMethodCall;
use RectorPrefix20220606\Ssch\TYPO3Rector\Rector\v10\v1\BackendUtilityEditOnClickRector;
use RectorPrefix20220606\Ssch\TYPO3Rector\Rector\v10\v1\RefactorInternalPropertiesOfTSFERector;
use RectorPrefix20220606\Ssch\TYPO3Rector\Rector\v10\v1\RegisterPluginWithVendorNameRector;
use RectorPrefix20220606\Ssch\TYPO3Rector\Rector\v10\v1\RemoveEnableMultiSelectFilterTextfieldRector;
use RectorPrefix20220606\Ssch\TYPO3Rector\Rector\v10\v1\SendNotifyEmailToMailApiRector;
return static function (RectorConfig $rectorConfig) : void {
    $rectorConfig->import(__DIR__ . '/../config.php');
    $rectorConfig->rule(RegisterPluginWithVendorNameRector::class);
    $rectorConfig->rule(BackendUtilityEditOnClickRector::class);
    $rectorConfig->ruleWithConfiguration(PropertyFetchToMethodCallRector::class, [new PropertyFetchToMethodCall('TYPO3\\CMS\\Backend\\History\\RecordHistory', 'changeLog', 'getChangeLog', 'setChangelog', ['bla']), new PropertyFetchToMethodCall('TYPO3\\CMS\\Backend\\History\\RecordHistory', 'lastHistoryEntry', 'getLastHistoryEntryNumber', null, [])]);
    $rectorConfig->ruleWithConfiguration(RenameMethodRector::class, [new MethodCallRename('TYPO3\\CMS\\Backend\\History\\RecordHistory', 'createChangeLog', 'getChangeLog'), new MethodCallRename('TYPO3\\CMS\\Backend\\History\\RecordHistory', 'getElementData', 'getElementInformation'), new MethodCallRename('TYPO3\\CMS\\Backend\\History\\RecordHistory', 'createMultipleDiff', 'getDiff'), new MethodCallRename('TYPO3\\CMS\\Backend\\History\\RecordHistory', 'setLastHistoryEntry', 'setLastHistoryEntryNumber')]);
    $rectorConfig->rule(SendNotifyEmailToMailApiRector::class);
    $rectorConfig->rule(RefactorInternalPropertiesOfTSFERector::class);
    $rectorConfig->rule(RemoveEnableMultiSelectFilterTextfieldRector::class);
};
