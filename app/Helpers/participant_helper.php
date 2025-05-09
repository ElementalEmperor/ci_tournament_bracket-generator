<?php
use CodeIgniter\CodeIgniter;

if (!function_exists('getParticipantsAndReusedGroupsInTournament')) {
    /**
     *
     * @param int $tournament_id
     * @return array
     */
    function getParticipantsAndReusedGroupsInTournament ($tournament_id, $hash = null)
    {
        $participantsModel = model('\App\Models\ParticipantModel');
        $groupMembersModel = model('\App\Models\GroupedParticipantsModel');
        $participants = [];
        if ($tournament_id) {
            $participants = $participantsModel->where(['participants.tournament_id' => $tournament_id])->withGroupInfo()->findAll();
        } else {
            $participants = $participantsModel->where(['participants.tournament_id' => $tournament_id, 'sessionid' => $hash])->withGroupInfo()->findAll();
        }

        $reusedGroups = [];
        if ($participants) {
            foreach ($participants as $participant) {
                if (isset($participant['g_id']) && $participant['g_id'] && !in_array($participant['g_id'], $reusedGroups)) {
                    if (count($groupMembersModel->where('group_id', $participant['g_id'])->groupBy('tournament_id')->findAll()) > 1) {
                        $reusedGroups[] = intval($participant['g_id']);
                    }
                }
            }
        }
        
        return ['participants' => $participants, 'reusedGroups' => $reusedGroups];
    }
}