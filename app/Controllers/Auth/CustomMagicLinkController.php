<?php

namespace App\Controllers\Auth;

use CodeIgniter\Shield\Controllers\MagicLinkController;
use CodeIgniter\Events\Events;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\I18n\Time;
use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Models\LoginModel;
use CodeIgniter\Shield\Models\UserIdentityModel;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Traits\Viewable;
use SendGrid;
use SendGrid\Mail\Mail;

class CustomMagicLinkController extends MagicLinkController
{
    public function __construct() {
        parent::__construct();
    }
    
    /**
     * Receives the email from the user, creates the hash
     * to a user identity, and sends an email to the given
     * email address.
     *
     * @return RedirectResponse|string
     */
    public function loginAction()
    {
        if (! setting('Auth.allowMagicLinkLogins')) {
            return redirect()->route('login')->with('error', lang('Auth.magicLinkDisabled'));
        }

        // Validate email format
        $rules = $this->getValidationRules();
        if (! $this->validateData($this->request->getPost(), $rules, [], config('Auth')->DBGroup)) {
            return redirect()->route('magic-link')->with('errors', $this->validator->getErrors());
        }

        // Check if the user exists
        $email = $this->request->getPost('email');
        $user  = $this->provider->findByCredentials(['email' => $email]);

        if ($user === null) {
            return redirect()->route('magic-link')->with('error', lang('Auth.invalidEmail'));
        }

        /** @var UserIdentityModel $identityModel */
        $identityModel = model(UserIdentityModel::class);

        // Delete any previous magic-link identities
        $identityModel->deleteIdentitiesByType($user, Session::ID_TYPE_MAGIC_LINK);

        // Generate the code and save it as an identity
        helper('text');
        $token = random_string('crypto', 20);

        $identityModel->insert([
            'user_id' => $user->id,
            'type'    => Session::ID_TYPE_MAGIC_LINK,
            'secret'  => $token,
            'expires' => Time::now()->addSeconds(setting('Auth.magicLinkLifetime')),
        ]);

        /** @var IncomingRequest $request */
        $request = service('request');

        $ipAddress = $request->getIPAddress();
        $userAgent = (string) $request->getUserAgent();
        $date      = Time::now()->toDateTimeString();

        // Send the user an email with the code
        $email = service('email');
        if ($email->send($user->email, lang('Auth.magicLinkSubject'), $this->view(
            setting('Auth.views')['magic-link-email'],
            ['token' => $token, 'ipAddress' => $ipAddress, 'userAgent' => $userAgent, 'date' => $date],
            ['debug' => false]
        )) === false) {
            log_message('error', $email->printDebugger(['headers']));

            return redirect()->route('magic-link')->with('error', lang('Auth.unableSendEmailToUser', [$user->email]));
        }

        return $this->displayMessage();
    }
}