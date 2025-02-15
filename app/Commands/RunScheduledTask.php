<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class RunScheduledTask extends BaseCommand
{
    /**
     * The Command's Group
     *
     * @var string
     */
    protected $group = 'Tasks';

    /**
     * The Command's Name
     *
     * @var string
     */
    protected $name = 'task:run';

    /**
     * The Command's Description
     *
     * @var string
     */
    protected $description = 'Run the scheduled tasks.';

    /**
     * The Command's Usage
     *
     * @var string
     */
    protected $usage = 'task:run [arguments] [options]';

    /**
     * The Command's Arguments
     *
     * @var array
     */
    protected $arguments = [];

    /**
     * The Command's Options
     *
     * @var array
     */
    protected $options = [];

    /**
     * Actually execute a command.
     *
     * @param array $params
     */
    public function run(array $params)
    {
        $voteLibrary = new \App\Libraries\VoteLibrary();
        $schedulesModel = model('\App\Models\SchedulesModel');
        $schedules = $schedulesModel->where(['result' => 0])->findAll();
        
        if ($schedules) {
            foreach($schedules as $schedule) {
                $schedule_time = new \DateTime($schedule['schedule_time']);
                $current_time = new \DateTime();
                
                if ($schedule['schedule_name'] == SCHEDULE_NAME_ROUNDUPDATE && $current_time >= $schedule_time) {
                    $voteLibrary->finalizeRound($schedule['tournament_id'], $schedule['round_no']);

                    $schedulesModel->update($schedule['id'], ['result' => 1]);
                }
            }
        }

        /** Remove expired tournaments */
        $tournamentsModel = model('\App\Models\TournamentModel');
        $tournamentLibrary = new \App\Libraries\TournamentLibrary();

        $tournaments = $tournamentsModel->where(['user_id' => 0])->findAll();
        foreach ($tournaments as $tournament) {
            if(time() - strtotime($tournament['created_at']) > 86400){
                /** Remove expired temp tournaments from cookie value */
                $tournamentLibrary->deleteTournament($tournament['id']);
            }
        }

        return true;
    }
}