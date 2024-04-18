<?php

namespace Agents\Integration\Me1C;

use CAllAgent;

trait ScheduleRunOnceTrait
{
    public static function scheduleRunOnce(int $id, int $delay = 0): void
    {
        $agentName = __CLASS__ . "::runOnce({$id});";
        $nextExec = date_create("+{$delay}min")->format('d.m.Y H:i:s');

        CAllAgent::Add([
            'MODULE_ID'      => '',
            'SORT'           => 100,
            'NAME'           => $agentName,
            'ACTIVE'         => 'Y',
            'AGENT_INTERVAL' => 0,
            'IS_PERIOD'      => 'N',
            'USER_ID'        => '',
            'NEXT_EXEC'      => $nextExec
        ]);
    }
}
