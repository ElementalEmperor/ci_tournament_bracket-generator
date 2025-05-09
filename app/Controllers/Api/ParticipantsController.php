<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Config\UploadConfig;

class ParticipantsController extends BaseController
{
    protected $participantsModel;
    protected $bracketsModel;
    protected $tournamentsModel;
    protected $votesModel;
    protected $groupedParticipantsModel;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        $this->participantsModel = model('\App\Models\ParticipantModel');
        $this->bracketsModel = model('\App\Models\BracketModel');
        $this->tournamentsModel = model('\App\Models\TournamentModel');
        $this->votesModel = model('\App\Models\VotesModel');
        $this->groupedParticipantsModel = model('\App\Models\GroupedParticipantsModel');
    }

    public function getParticipants() {
        // Check if it's an AJAX request
        if ($this->request->isAJAX()) {
            $participant_name = $this->request->getPost('participant'); // Get the posted data
            
            $participants = $this->getParticipantsList($participant_name);
            
            if ($participants) {
                $participants = array_values($participants);
                
                $keys = array_column($participants, 'tournaments_won');

                array_multisort($keys, SORT_DESC, $participants);
            }
            
            return $this->response->setStatusCode(ResponseInterface::HTTP_OK)
                                    ->setJSON($participants);
        }

        // If not an AJAX request, return a 403 error
        return $this->response->setStatusCode(ResponseInterface::HTTP_FORBIDDEN)
                              ->setJSON(['status' => 'error', 'message' => 'Invalid request']);
    }

    public function getAnalysis()
    {
        // Check if it's an AJAX request
        if ($this->request->isAJAX()) {
            $participants = $this->getParticipantsList();
            $type = 'tournaments_won';

            if ($this->request->getPost('type') == 'bracket') {
                $type = 'brackets_won';
            }

            if ($this->request->getPost('type') == 'score') {
                $type = 'accumulated_score';
            }

            if ($this->request->getPost('type') == 'votes') {
                $type = 'votes';
            }

            $keys = array_column($participants, $type);
            array_multisort($keys, SORT_DESC, $participants);
            
            $list = [];
            if ($participants) {
                $i = 0;
                foreach ($participants as $participant) {
                    if ($i > 4) {
                        break;
                    }

                    $list[] = $participant;
                    $i++;
                }
            }

            $tournaments_count = $this->tournamentsModel->where('status', TOURNAMENT_STATUS_COMPLETED)->countAllResults();
            
            return $this->response->setStatusCode(ResponseInterface::HTTP_OK)
                                    ->setJSON(['participants' => $list, 'tournaments_count' => $tournaments_count]);
        }

        // If not an AJAX request, return a 403 error
        return $this->response->setStatusCode(ResponseInterface::HTTP_FORBIDDEN)
                              ->setJSON(['status' => 'error', 'message' => 'Invalid request']);
    }

    private function getParticipantsList($participant_name = null)
    {
        $userSettingService = service('userSettings');

        if ($participant_name) {
            $participants = $this->participantsModel->like('name', $participant_name)->withGroupInfo()->findAll();
        } else {
            $participants = $this->participantsModel->withGroupInfo()->findAll();
        }
        
        if ($participants) {
            $newList = [];
            $registered_users = [];
            $groups = [];
            foreach ($participants as $participant) {
                $brackets = $this->bracketsModel->where(['winner' => $participant['id']])->findAll();
                $participant['brackets_won'] = ($brackets) ? count($brackets) : 0;

                $finalBrackets = $this->bracketsModel->where(['winner' => $participant['id'], 'final_match' => 1])->findAll();
                $participant['tournaments_won'] = ($finalBrackets) ? count($finalBrackets) : 0;

                $participant['won_tournaments'] = [];
                $won_tournament_names = [];
                if ($finalBrackets) {
                    foreach ($finalBrackets as $f_bracket) {
                        $won_tournament = $this->tournamentsModel->find($f_bracket['tournament_id']);
                        $participant['won_tournaments'][] = $won_tournament;
                        $won_tournament_names[] = strtolower($won_tournament['name']);
                    }
                }

                if ($this->request->getPost('won_tournament')) {
                    if (empty(array_filter($won_tournament_names, fn($str) => stripos($str, $this->request->getPost('won_tournament')) !== false))) {
                        continue;
                    }
                }

                $scores = $this->calculateScores($participant['id'], $brackets);
                $participant['top_score'] = $scores['top_score'];
                $participant['accumulated_score'] = $scores['total_score'];

                $votes = $this->votesModel->where('participant_id', $participant['id'])->findAll();
                $participant['votes'] = ($votes) ? count($votes) : 0;
                
                $participant['email'] = null;
                if ($participant['name'][0] == '@' && $participant['registered_user_id']) {
                    $registered_user_id = $participant['registered_user_id'];
                    if (!$userSettingService->get('hide_email_participant', $participant['registered_user_id'])) {
                        $registered_user = auth()->getProvider()->findById($registered_user_id);
                        $participant['email'] = $registered_user ? $registered_user->email : null;
                    }
                    
                    if (isset($registered_users[$registered_user_id])) {
                        $registered_users[$registered_user_id]['brackets_won'] += $participant['brackets_won'];
                        $registered_users[$registered_user_id]['tournaments_won'] += $participant['tournaments_won'];
                        $registered_users[$registered_user_id]['accumulated_score'] += $participant['accumulated_score'];
                        $registered_users[$registered_user_id]['votes'] += $participant['votes'];
                        
                        if (count($participant['won_tournaments'])) {
                            $registered_users[$registered_user_id]['won_tournaments'] = array_merge($registered_users[$registered_user_id]['won_tournaments'], $participant['won_tournaments']);
                        }
                    } else {
                        $registered_users[$registered_user_id] = $participant;
                    }
                } elseif ($participant['is_group']) {
                    $tournament_ids = $this->participantsModel->where('group_id', $participant['group_id'])->findColumn('tournament_id');

                    if (isset($groups[$participant['group_id']])) {
                        $groups[$participant['group_id']]['brackets_won'] += $participant['brackets_won'];
                        $groups[$participant['group_id']]['tournaments_won'] += $participant['tournaments_won'];
                        $groups[$participant['group_id']]['accumulated_score'] += $participant['accumulated_score'];
                        $groups[$participant['group_id']]['votes'] += $participant['votes'];
                        
                        if (count($participant['won_tournaments'])) {
                            $groups[$participant['group_id']]['won_tournaments'] = array_merge($groups[$participant['group_id']]['won_tournaments'], $participant['won_tournaments']);
                        }
                    } else {
                        $groups[$participant['group_id']] = $participant;
                    }

                    $groups[$participant['group_id']]['tournaments_list'] = $this->tournamentsModel->whereIn('id', $tournament_ids)->select(['id', 'name'])->findAll();
                } else {
                    if ($this->request->getPost('tournament')) {
                        $participant['tournaments_list'] = $this->tournamentsModel->where('id', $participant['tournament_id'])->like('name', $this->request->getPost('tournament'))->select(['id', 'name'])->findAll();
                    } else {
                        $participant['tournaments_list'] = $this->tournamentsModel->where('id', $participant['tournament_id'])->select(['id', 'name'])->findAll();
                    }

                    $newList[$participant['id']] = $participant;
                }
            }

            if (isset($registered_users) && $registered_users) {
                foreach ($registered_users as $user_id => $user) {
                    $tournament_ids = $this->participantsModel->where('registered_user_id', $user_id)->findColumn('tournament_id');
                    if ($this->request->getPost('tournament')) {
                        $user['tournaments_list'] = $this->tournamentsModel->whereIn('id', $tournament_ids)->like('name', $this->request->getPost('tournament'))->select(['id', 'name'])->findAll();

                        if (!$user['tournaments_list']) {
                            continue;
                        }
                    }
                    
                    $user['tournaments_list'] = $this->tournamentsModel->whereIn('id', $tournament_ids)->select(['id', 'name'])->findAll();
                    $newList['u_' . $user_id] = $user;
                }
            }

            if (isset($groups) && $groups) {
                foreach ($groups as $group_id => $group) {
                    $group['members'] = '';
                    // Fetch the group members and plus the score and counts
                    $members = $this->groupedParticipantsModel->where('grouped_participants.group_id', $group_id)->details()->findAll();
                    if ($members) {
                        foreach ($members as $index => $member) {
                            if (!$member['id']) {
                                continue;
                            }
                            
                            $user_id = $member['registered_user_id'] ? 'u_' . $member['registered_user_id'] : $member['id'];

                            if (isset($newList[$user_id])) {
                                $newList[$user_id]['brackets_won'] += $group['brackets_won'];
                                $newList[$user_id]['tournaments_won'] += $group['tournaments_won'];
                                $newList[$user_id]['top_score'] += $group['top_score'];
                                $newList[$user_id]['accumulated_score'] += $group['accumulated_score'];
                                $newList[$user_id]['votes'] += $group['votes'];
                                $newList[$user_id]['won_tournaments'] = array_merge($newList[$user_id]['won_tournaments'], $group['won_tournaments']);
                                $newList[$user_id]['tournaments_list'] = array_merge($newList[$user_id]['tournaments_list'], $group['tournaments_list']);
                            }
                            
                            $group['members'] .= $member['name'];
                            if ($index < count($members) - 1) {
                                $group['members'] .= '<br/>';
                            }
                        }
                    }

                    $tournament_ids = $this->participantsModel->where('group_id', $group_id)->findColumn('tournament_id');
                    if ($this->request->getPost('tournament')) {
                        $group['tournaments_list'] = $this->tournamentsModel->whereIn('id', $tournament_ids)->like('name', $this->request->getPost('tournament'))->select(['id', 'name'])->findAll();

                        if (!$user['tournaments_list']) {
                            continue;
                        }
                    }
                    
                    $group['tournaments_list'] = $this->tournamentsModel->whereIn('id', $tournament_ids)->select(['id', 'name'])->findAll();
                    $newList['g_' . $group_id] = $group;
                }
            }

            $participants = $newList;
        }

        return $participants;
    }

    public function addParticipant($names = null)
    {
        if (!$names) {
            $names = $this->request->getPost('name');
        }

        $tournament_id = $this->request->getPost('tournament_id') ? $this->request->getPost('tournament_id') : 0;
        $user_id = $this->request->getPost('user_id') ? $this->request->getPost('user_id') : 0;
        
        $hash = $this->request->getPost('hash');
        
        $participants = []; $inserted_count = 0;
        if ($names) {
            $userProvider = auth()->getProvider();
            foreach ($names as $name) {
                if ($name) {
                    $participant = new \App\Entities\Participant([
                        'name' => $name,
                        'user_id' => $user_id,
                        'tournament_id' => $tournament_id,
                        'active' => 1,
                        'sessionid' => $hash
                    ]);
                    if ($name[0] == '@') {
                        $name = trim($name, '@');
                        $user = $userProvider->where('username', $name)->first();
                        if ($user) {
                            $participant->registered_user_id = $user->id;
                        }
                    }

                    $this->participantsModel->insert($participant);
                    $participant->id = $this->participantsModel->getInsertID();
                    $participants[] = $participant;
                    $inserted_count++;
                }
            }
        }

        helper('participant_helper');            
        if ($tournament_id) {
            $list = getParticipantsAndReusedGroupsInTournament($tournament_id);
        } else {
            $list = getParticipantsAndReusedGroupsInTournament($tournament_id, $this->request->getPost('hash'));
        }

        return $this->response->setStatusCode(ResponseInterface::HTTP_OK)
                                ->setJSON(['result' => 'success', "participants"=> $list['participants'],"reusedGroups"=> $list['reusedGroups'], 'count' => $inserted_count]);
    }

    public function updateParticipant($id)
    {
        $participant = $this->participantsModel->find($id);
        
        if($this->request->getPost('name')) {
            $participant['name'] = $this->request->getPost('name');
        }

        if ($participant['name'][0] == '@') {
            $name = trim($participant['name'], '@');
            $user = auth()->getProvider()->where('username', $name)->first();
            if ($user) {
                $participant['registered_user_id'] = $user->id;
            }
        }

        $uploadConfig = new UploadConfig();
        
		$file = $this->request->getFile('image');
        if($file){
            $filepath = '';
            if (! $file->hasMoved()) {
                $filepath = '/uploads/' . $file->store($uploadConfig->participantImagesUploadPath);
                $participant['image'] = $filepath;

                $brackets = $this->bracketsModel->where(['tournament_id'=> $participant['tournament_id']])->findAll();
                foreach($brackets as $bracket){
                    $teamnames = json_decode($bracket['teamnames'], true);
                    $temp = [];
                    if ($teamnames) {
                        foreach ($teamnames as $teamname) {

                            if ($teamname && $teamname['id'] == $participant['id']) {
                                $teamname['image'] = $filepath;
                            }
                            $temp[] = $teamname;
                        }
                        $new_bracket = $bracket;
                        $new_bracket['teamnames'] = json_encode($temp);
                        $this->bracketsModel->update($new_bracket['id'], $new_bracket);
                    }
                }
            }
        }
        
        if($this->request->getPost('action') == 'removeImage'){
            $participant['image'] = '';
            $brackets = $this->bracketsModel->where(['tournament_id'=> $participant['tournament_id']])->findAll();
            foreach($brackets as $bracket){
                $teamnames = json_decode($bracket['teamnames'], true);
                $temp = [];
                foreach($teamnames as $teamname){

                    if($teamname && $teamname['id'] == $participant['id']){
                        $teamname['image'] = '';
                    }
                    $temp[] = $teamname;
                }
                $new_bracket = $bracket;
                $new_bracket['teamnames'] = json_encode($temp);
                $this->bracketsModel->update($new_bracket['id'], $new_bracket);
            }

        }
        $this->participantsModel->update($id, $participant);

        return json_encode(array('result' => 'success', 'data' => $participant));
    }

    public function deleteParticipant($id)
    {
        $participant = $this->participantsModel->find($id);
        $tournament_id = $participant ? $participant['tournament_id'] : 0;

        $this->participantsModel->where('id', $id)->delete();

        helper('participant_helper');            
        if ($tournament_id) {
            $list = getParticipantsAndReusedGroupsInTournament($tournament_id);
        } else {
            $list = getParticipantsAndReusedGroupsInTournament($tournament_id, $this->request->getPost('hash'));
        }

        return $this->response->setStatusCode(ResponseInterface::HTTP_OK)
                                ->setJSON(['status' => 'success', "participants"=> $list['participants'],"reusedGroups"=> $list['reusedGroups']]);
    }
    
    public function deleteParticipants()
    {
        if ($participant_ids = $this->request->getPost('p_ids')) {
            $this->participantsModel->whereIn('id', $participant_ids)->delete();
        } else {
            return json_encode(array('result' => 'failed', 'msg' => 'There is not participant selected'));
        }

        $user_id = auth()->user() ? auth()->user()->id : 0;
        if ($user_id) {
            $participants = $this->participantsModel->where(['tournament_id' => 0, 'user_id' => $user_id])->findAll();
        } else {
            $participants = $this->participantsModel->where(['tournament_id' => 0, 'sessionid' => $this->request->getPost('hash')])->findAll();
        }

        return json_encode(array('result' => 'success', 'count' => count($participants), 'participants' => $participants));
    }
    
    public function clearParticipants()
    {
        if ($tournament_id = $this->request->getGet('t_id')) {
            $this->participantsModel->where(['user_id' => auth()->user()->id, 'tournament_id' => $tournament_id])->delete();
        } else {
            if (auth()->user()) {
                $this->participantsModel->where(['user_id' => auth()->user()->id, 'tournament_id' => 0])->delete();
            } else {
                $hash = $this->request->getPost('hash');
                $this->participantsModel->where(['sessionid' => $hash, 'tournament_id' => 0])->delete();
            }
        }

        return json_encode(array('result' => 'success'));
    }
    
    public function importParticipants()
    {
        $validationRule = [
            'file' => [
                'label' => 'CSV File',
                'rules' => [
                    'uploaded[file]',
                    'ext_in[file,csv]',
                ],
                'errors' => [
                    'uploaded' => 'Please upload a file.',
                    'ext_in' => 'The uploaded file must be a valid CSV.',
                ],
            ],
        ];
        
        if (!$this->validateData([], $validationRule)) {
            $data = ['errors' => $this->validator->getErrors()];
            
            return $this->response->setJSON($data);
        }

        $uploadConfig = new UploadConfig();

		$file = $this->request->getFile('file');
        $filepath = '';
        if (! $file->hasMoved()) {
            $filepath = WRITEPATH . 'uploads/' . $file->store($uploadConfig->csvUploadPath);
        }
        
        if (!file_exists($filepath)) {
            return $this->response->setJSON(['errors' => "Imported file was not saved correctly"]);
        }

		$arr_file 		= explode('.', $filepath);
		$extension 		= end($arr_file);
		if('csv' == $extension) {
			$reader 	= new \PhpOffice\PhpSpreadsheet\Reader\Csv();
		} else {
			$reader 	= new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
		}
		$spreadsheet 	= $reader->load($filepath);
		$sheet_data 	= $spreadsheet->getActiveSheet()->toArray();
        
		$data 			= [];
		foreach($sheet_data as $key => $val) {
			if($key != 0) {
                $data[] = $val[0];
			}
		}
        
        return $this->response->setJSON(['result' => 'success', 'names' => $data]);
    }

    public function calculateScores($participant_id, $brackets) {
        $totalScore = 0;
        $topScore = 0;
        $tournamentSettings = [];
        $scores_by_tournaments = [];

        if ($brackets) {
            foreach ($brackets as $bracket) {
                $bracket_score = 0;
                $increment_score = 0;
                $increment_score_type = 0;

                if (!isset($tournamentSettings[$bracket['tournament_id']])) {
                    $tournamentSettings[$bracket['tournament_id']] = $this->tournamentsModel->find($bracket['tournament_id']);
                }

                if ($tournamentSettings[$bracket['tournament_id']]['type'] == TOURNAMENT_TYPE_KNOCKOUT) {
                    if ($bracket['knockout_final']) {
                        continue;
                    }
                } else {
                    if ($bracket['final_match']) {
                        continue;
                    }
                }
                
                $bracket_score = ($tournamentSettings[$bracket['tournament_id']]['score_enabled']) ? $tournamentSettings[$bracket['tournament_id']]['score_bracket'] : 0;
                $increment_score = ($tournamentSettings[$bracket['tournament_id']]['increment_score_enabled']) ? $tournamentSettings[$bracket['tournament_id']]['increment_score'] : 0;
                $increment_score_type = $tournamentSettings[$bracket['tournament_id']]['increment_score_type'];

                if (!isset($scores_by_tournaments[$bracket['tournament_id']])) {
                    $scores_by_tournaments[$bracket['tournament_id']] = 0;
                }

                if ($increment_score_type == TOURNAMENT_SCORE_INCREMENT_PLUS) {
                    $scores_by_tournaments[$bracket['tournament_id']] += $bracket_score + $increment_score * ($bracket['roundNo'] - 1);
                }

                if ($increment_score_type == TOURNAMENT_SCORE_INCREMENT_MULTIPLY) {
                    if ($bracket['roundNo'] == 1) {
                        $scores_by_tournaments[$bracket['tournament_id']] = $bracket_score;
                    } else {
                        $scores_by_tournaments[$bracket['tournament_id']] += $scores_by_tournaments[$bracket['tournament_id']] * $increment_score;
                    }
                }
            }
        }

        $totalScore = array_sum($scores_by_tournaments);
        $topScore = ($scores_by_tournaments) ? max($scores_by_tournaments) : 0;

        return ['total_score' => $totalScore, 'top_score' => $topScore];
    }
    
    public function export(){
        $participants = $this->participantsModel->findAll();
        
        $filename = 'participants' . date('Ymd') . '.csv';

        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=\"$filename\"");

        $output = fopen('php://output', 'w');

        // Add the CSV column headers
        fputcsv($output, ['ID', 'Participant Name', 'Brackets Won', 'Tournaments Won', 'Won Tournaments', 'Participated Tournaments', 'Accumulated Score', 'Votes']);

        // Fetch the data and write it to the CSV
        if ($participants) {
            $newList = [];
            $registered_users = [];
            foreach ($participants as $participant) {
                $brackets = $this->bracketsModel->where(['winner' => $participant['id']])->findAll();
                $participant['brackets_won'] = ($brackets) ? count($brackets) : 0;

                $finalBrackets = $this->bracketsModel->where(['winner' => $participant['id'], 'final_match' => 1])->findAll();
                $participant['tournaments_won'] = ($finalBrackets) ? count($finalBrackets) : 0;

                $participant['won_tournaments'] = [];
                $won_tournament_names = [];
                if ($finalBrackets) {
                    foreach ($finalBrackets as $f_bracket) {
                        $won_tournament = $this->tournamentsModel->find($f_bracket['tournament_id']);
                        $participant['won_tournaments'][] = $won_tournament;
                        $won_tournament_names[] = strtolower($won_tournament['name']);
                    }
                }

                if ($this->request->getPost('won_tournament')) {
                    if (empty(array_filter($won_tournament_names, fn($str) => stripos($str, $this->request->getPost('won_tournament')) !== false))) {
                        continue;
                    }
                }

                $scores = $this->calculateScores($participant['id'], $brackets);
                $participant['top_score'] = $scores['top_score'];
                $participant['accumulated_score'] = $scores['total_score'];

                $votes = $this->votesModel->where('participant_id', $participant['id'])->findAll();
                $participant['votes'] = ($votes) ? count($votes) : 0;

                if ($participant['name'][0] == '@' && $participant['registered_user_id']) {
                    $registered_user_id = $participant['registered_user_id'];
                    $tournament_ids = $this->participantsModel->where('registered_user_id', $registered_user_id)->findColumn('tournament_id');
                                        
                    if (isset($registered_users[$registered_user_id])) {
                        $registered_users[$registered_user_id]['brackets_won'] += $participant['brackets_won'];
                        $registered_users[$registered_user_id]['tournaments_won'] += $participant['tournaments_won'];
                        $registered_users[$registered_user_id]['accumulated_score'] += $participant['accumulated_score'];
                        $registered_users[$registered_user_id]['votes'] += $participant['votes'];
                        
                        if (count($participant['won_tournaments'])) {
                            $registered_users[$registered_user_id]['won_tournaments'] = array_merge($registered_users[$registered_user_id]['won_tournaments'], $participant['won_tournaments']);
                        }
                    } else {
                        $registered_users[$registered_user_id] = $participant;
                    }

                    $registered_users[$registered_user_id]['tournaments_list'] = $this->tournamentsModel->whereIn('id', $tournament_ids)->select(['id', 'name'])->findAll();
                } else {
                    if ($this->request->getPost('tournament')) {
                        $participant['tournaments_list'] = $this->tournamentsModel->where('id', $participant['tournament_id'])->like('name', $this->request->getPost('tournament'))->select(['id', 'name'])->findAll();
                        if ($participant['tournaments_list']) {
                            $newList[] = $participant;
                        }
                    } else {
                        $participant['tournaments_list'] = $this->tournamentsModel->where('id', $participant['tournament_id'])->select(['id', 'name'])->findAll();
                        $newList[] = $participant;
                    }
                }
            }

            if ($registered_users) {
                foreach ($registered_users as $user) {
                    if ($this->request->getPost('tournament')) {
                        if ($user['tournaments_list']) {
                            array_push($newList, $user);
                        }
                    } else {
                        array_push($newList, $user);
                    }
                    
                }
            }
            
            $keys = array_column($newList, 'tournaments_won');
            array_multisort($keys, SORT_DESC, $newList);

            foreach ($newList as $participant) {
                $won_list = '';
                if ($participant['won_tournaments']) {
                    foreach ($participant['won_tournaments'] as $i => $won_t) {
                        $won_list .= $won_t['name'];

                        if ($i < count($participant['won_tournaments']) - 1) {
                            $won_list .= ', ';
                        }
                    }
                }

                $tournament_list = '';
                if ($participant['tournaments_list']) {
                    foreach ($participant['tournaments_list'] as $i => $tm) {
                        $tournament_list .= $tm['name'];

                        if ($i < count($participant['tournaments_list']) - 1) {
                            $tournament_list .= ', ';
                        }
                    }
                }

                fputcsv($output, [
                    $participant['id'],
                    $participant['name'],
                    $participant['brackets_won'],
                    $participant['tournaments_won'],
                    $won_list,
                    $tournament_list,
                    $participant['accumulated_score'],
                    $participant['votes']
                ]);
            }
        }

        fclose($output);
        exit;
    }
}