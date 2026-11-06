<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    /**
     * Helpers loaded automatically
     */
    protected $helpers = ['url', 'form'];

    /**
     * Common Services
     */
    protected $session;
    protected $db;

    /**
     * Default Layout
     */
    protected $layout = 'layouts/default';


    /**
     * Init Controller
     */
    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController(
            $request,
            $response,
            $logger
        );

        $this->session =
            service('session');

        $this->db =
            \Config\Database::connect();
    }


    /**
     * Render View with Layout
     */
    protected function view(
        string $view,
        array $data = [],
        ?string $layout = null
    ) {
        $layout =
            $layout ?? $this->layout;


        $data['content'] =
            view($view, $data);


        return view(
            $layout,
            $data
        );
    }


    /**
     * Send Email OTP
     *
     * @param string $email
     * @param string $otp
     * @param string $purpose
     * @param string $userType
     *
     * $purpose:
     * registration
     * login
     * forgot_password
     *
     * $userType:
     * customer
     * seller
     */
    protected function sendEmailOtp(
        string $email,
        string $otp,
        string $purpose = 'registration',
        string $userType = 'customer'
    ) {

        $emailService =
            \Config\Services::email();


        /**
         * -----------------------------------------
         * EMAIL SUBJECT
         * -----------------------------------------
         */

        if ($userType === 'seller') {

            if ($purpose === 'login') {

                $subject =
                    'Ajobmart Seller Login OTP';

            } elseif ($purpose === 'forgot_password') {

                $subject =
                    'Ajobmart Seller Password Reset OTP';

            } else {

                $subject =
                    'Ajobmart Seller Registration OTP';
            }

        } else {

            if ($purpose === 'login') {

                $subject =
                    'Ajobmart Login OTP';

            } elseif ($purpose === 'forgot_password') {

                $subject =
                    'Ajobmart Password Reset OTP';

            } else {

                $subject =
                    'Ajobmart Registration OTP';
            }
        }


        /**
         * -----------------------------------------
         * EMAIL VIEW
         * -----------------------------------------
         */

        $message = view(
            'emails/otp_email',
            [
                'otp'      => $otp,
                'purpose'  => $purpose,
                'userType' => $userType
            ]
        );


        /**
         * -----------------------------------------
         * EMAIL CONFIG
         * -----------------------------------------
         */

        $emailService->clear();


        $emailService->setTo(
            $email
        );


        $emailService->setSubject(
            $subject
        );


        $emailService->setMessage(
            $message
        );


        /**
         * -----------------------------------------
         * SEND
         * -----------------------------------------
         */

        if (!$emailService->send()) {

            log_message(
                'error',
                'Ajobmart OTP email failed for: ' .
                $email .
                ' | ' .
                $emailService->printDebugger(
                    ['headers']
                )
            );

            return false;
        }


        return true;
    }
}