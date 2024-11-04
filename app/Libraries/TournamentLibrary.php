<?php

namespace App\Libraries;

class TournamentLibrary
{
    protected $bracketsModel;
    protected $participantsModel;
    protected $tournamentsModel;
    protected $votesModel;
    protected $shareSettingsModel;
    protected $musicSettingsModel;
    protected $roundSettingsModel;
    protected $schedulesModel;
    protected $logActionsModel;
    
    public function __construct()
    {
        // This is called when the library is initialized
        // You can load models, helpers, or any setup here
        $this->bracketsModel = model('\App\Models\BracketModel');
        $this->participantsModel = model('\App\Models\ParticipantModel');
        $this->tournamentsModel = model('\App\Models\TournamentModel');
        $this->votesModel = model('\App\Models\VotesModel');
        $this->shareSettingsModel = model('\App\Models\ShareSettingsModel');
        $this->musicSettingsModel = model('\App\Models\MusicSettingModel');
        $this->roundSettingsModel = model('\App\Models\TournamentRoundSettingsModel');
        $this->schedulesModel = model('\App\Models\SchedulesModel');
        $this->logActionsModel = model('\App\Models\LogActionsModel');
    }

    public function deleteTournament($tournament_id)
    {
        $this->shareSettingsModel->where(['tournament_id' => $tournament_id])->delete();
        $this->roundSettingsModel->where(['tournament_id' => $tournament_id])->delete();
        $this->logActionsModel->where(['tournament_id' => $tournament_id])->delete();
        $this->votesModel->where(['tournament_id' => $tournament_id])->delete();

        $participants = $this->participantsModel->where(['tournament_id' => $tournament_id])->findAll();
        if ($participants) {
            foreach ($participants as $participant) {
                if ($participant['image']) {
                    unlink(WRITEPATH . $participant['image']);
                }
            }

            $this->participantsModel->where(['tournament_id' => $tournament_id])->delete();
        }

        $musicSettings = $this->musicSettingsModel->where(['tournament_id' => $tournament_id])->findAll();
        if ($musicSettings) {
            foreach ($musicSettings as $setting) {
                if ($setting['path']) {
                    unlink(WRITEPATH . 'uploads/' . $setting['path']);
                }
            }
        }
        $this->musicSettingsModel->where(['tournament_id' => $tournament_id])->delete();

        $this->bracketsModel->where(['tournament_id' => $tournament_id])->delete();
        
        $this->schedulesModel->where(['tournament_id' => $tournament_id])->delete();
        
        $this->tournamentsModel->where('id', $tournament_id)->delete();
        
        return true;
    }
}