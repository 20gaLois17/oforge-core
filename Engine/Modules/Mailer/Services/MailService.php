<?php

namespace Oforge\Engine\Modules\Mailer\Services;

use FrontendUserManagement\Models\User;
use FrontendUserManagement\Services\UserService;
use Insertion\Models\Insertion;
use Insertion\Services\InsertionService;
use InvalidArgumentException;
use Oforge\Engine\Modules\Core\Exceptions\ConfigElementNotFoundException;
use Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException;
use Oforge\Engine\Modules\Core\Helper\Statics;
use Oforge\Engine\Modules\Core\Services\ConfigService;
use Oforge\Engine\Modules\I18n\Helper\I18N;
use Oforge\Engine\Modules\Media\Twig\MediaExtension;
use Oforge\Engine\Modules\TemplateEngine\Core\Services\TemplateManagementService;
use Oforge\Engine\Modules\TemplateEngine\Core\Twig\CustomTwig;
use Oforge\Engine\Modules\TemplateEngine\Core\Twig\TwigOforgeDebugExtension;
use Oforge\Engine\Modules\TemplateEngine\Extensions\Twig\AccessExtension;
use Oforge\Engine\Modules\TemplateEngine\Extensions\Twig\SlimExtension;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Twig_Error_Loader;
use Doctrine\ORM\ORMException;

class MailService {

    private $mailer        = null;
    private $configService = null;
    private $twig          = null;

    /**
     * MailService constructor. Initialises PHP Mailer instance with configuration and dependencies
     *
     * @throws ConfigElementNotFoundException
     * @throws ORMException
     * @throws ServiceNotFoundException
     * @throws Twig_Error_Loader
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\Template\TemplateNotFoundException
     */
    function __construct() {

        /** @var  ConfigService $configService */
        $this->configService = Oforge()->Services()->get("config");
        // $config              = $this->configService->getGroupConfigs("mail")->toArray(2);


        /** @var PHPMailer $mailer */
        $this->mailer = new PHPMailer($exceptions = null);

        /** Default configuration */
        $this->mailer->isSMTP();
        $this->mailer->isHTML();
        $this->mailer->Encoding   = $this->mailer::ENCODING_BASE64;
        $this->mailer->CharSet    = $this->mailer::CHARSET_UTF8;

        /** Backend configuration */
        // TODO: use array helper to set config keys ?? don't use config service for this!!


        $this->mailer->Host        = $this->configService->get("mailer_host");
        $this->mailer->Port        = $this->configService->get("mailer_port");
        $this->mailer->Username    = $this->configService->get("mailer_smtp_username");
        $this->mailer->Password    = $this->configService->get("mailer_smtp_password");
        $this->mailer->SMTPAuth    = $this->configService->get("mailer_smtp_auth");
        $this->mailer->SMTPSecure  = $this->configService->get("mailer_smtp_secure");

        /** @var TemplateManagementService $templateManagementService */
        $templateManagementService = Oforge()->Services()->get("template.management");
        $activeTemplate            = $templateManagementService->getActiveTemplate()->getName();

        $templatePath              = Statics::TEMPLATE_DIR . DIRECTORY_SEPARATOR . $activeTemplate . DIRECTORY_SEPARATOR . 'MailTemplates';

        /** @var CustomTwig twig */
        $this->twig = new CustomTwig($templatePath, ['cache' => ROOT_PATH . DIRECTORY_SEPARATOR . Statics::CACHE_DIR . '/mailer']);
        $this->twig->addExtension(new \Oforge\Engine\Modules\CMS\Twig\AccessExtension());
        $this->twig->addExtension(new AccessExtension());
        $this->twig->addExtension(new MediaExtension());
        $this->twig->addExtension(new SlimExtension());
        $this->twig->addExtension(new TwigOforgeDebugExtension());

    }

    /**
     * Options = [
     * 'to'         => ['user@host.de' => 'user_name', user2@host.de => 'user2_name, ...],
     * 'cc'         => [],
     * 'bcc'        => [],
     * 'replyTo'    => [],
     * 'attachment' => [],
     * "subject"    => string,
     * "html"       => bool,
     * ]
     * TemplateData = ['key' = value, ... ]
     * @param array $options
     * @param array $templateData
     *
     * @return bool
     * @throws ConfigElementNotFoundException
     * @throws Exception
     * @throws ServiceNotFoundException
     */
    public function send(array $options, array $templateData = []) {

        $this->mailer->setFrom($this->getSenderAddress($options['from']), $this->configService->get('mailer_from_name'));

        try {
            /** Add Recipients ({to,cc,bcc}Addresses) */
            foreach ($options["to"] as $key => $value) {
                $this->mailer->addAddress($key, $value);
            }
            if (isset($options['cc'])) {
                foreach ($options["cc"] as $key => $value) {
                    $this->mailer->addCC($key, $value);
                }
            }
            if (isset($options['bcc'])) {
                foreach ($options["bcc"] as $key => $value) {
                    $this->mailer->addBCC($key, $value);
                }
            }
            if (isset($options['replyTo'])) {
                foreach ($options["replyTo"] as $key => $value) {
                    $this->mailer->addReplyTo($key, $value);
                }
            }

            /** Add Attachments: */
            if (isset($options['attachment'])) {
                foreach ($options["attachment"] as $key => $value) {
                    $this->mailer->addAttachment($key, $value);
                }
            }
            /** Generate Base-Url for Media */
            $conversationLink = 'http://';
            if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
                $conversationLink = 'https://';
            }

            $conversationLink        .= $_SERVER['HTTP_HOST'];
            $templateData['baseUrl'] = $conversationLink;

            /** Render HTML */
            $renderedTemplate = $this->renderMail($options, $templateData);

            /** Add Content */
            $this->mailer->Subject = $options["subject"];
            $this->mailer->Body    = $renderedTemplate;

            $this->mailer->send();

            Oforge()->Logger()->get("mailer")->info("Mail has been sent", [$options, $templateData]);

            return true;

        } catch(\Exception $e) {
            Oforge()->Logger()->get("mailer")->error("Mail has not been sent", [$this->mailer->ErrorInfo]);

            return false;
        }
    }

    /**
     *  Mailer refactorings:
     * 1. Load configuration in one db call --> do this now!
     * 2. Remove unnecessary validation code --> done!
     * 3. Use event system + batchSend() + SMTP keepAlive
     * 4. Move templates / logic to corresponding  --> later
     * 5. Don't create a new mailer instance on each send
     */


    /**
     * Checks if the specified template exists in active Theme, if not: Fallback to Base Theme
     *
     * @param array $options
     * @param array $templateData
     *
     * @return mixed
     * @throws ServiceNotFoundException
     * @throws Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderMail(array $options, array $templateData) {

        // check with twig loader, what is templatePath ???
        if (!file_exists($templatePath . DIRECTORY_SEPARATOR . $options['template'])) {
            $templatePath = Statics::TEMPLATE_DIR . DIRECTORY_SEPARATOR . Statics::DEFAULT_THEME . DIRECTORY_SEPARATOR . 'MailTemplates';
        }

        /** @var string $html */
        $html = $this->twig->fetch($template = $options['template'], $data = $templateData);

        /** @var  $inlineCssService */
        $inlineCssService = Oforge()->Services()->get('inline.css');

        return $inlineCssService->renderInlineCss($html);
    }

    /**
     * Looks up mailer backend options for custom sender and host information.
     *
     * @param string $key
     *
     * @return string
     * @throws ConfigElementNotFoundException
     * @throws ServiceNotFoundException
     */
    public function getSenderAddress($key = 'info') {
        $configService = Oforge()->Services()->get("config");

        $host = $configService->get('mailer_from_host');
        if (!$host) {
            throw new InvalidArgumentException("Error: Host is not set");
        }
        $sender = $configService->get('mailer_from_' . $key);

        $senderAddress = $sender . '@' . $host;

        return $senderAddress;
    }

    /**
     * @param $userId
     * @param $conversationId
     *
     * @return bool
     * @throws ConfigElementNotFoundException
     * @throws Exception
     * @throws ORMException
     * @throws ServiceNotFoundException
     */
    //TODO: do this in corresponding plugin
    public function sendNewMessageInfoMail($userId, $conversationId) {
        /** @var  UserService $userService */
        $userService = Oforge()->Services()->get('frontend.user.management.user');

        /** @var User $user */
        $user = $userService->getUserById($userId);

        $userMail      = $user->getEmail();
        $mailerOptions = [
            'to'       => [$userMail => $userMail],
            'from'     => 'no_reply',
            'subject'  => I18N::translate('mailer_subject_new_message'),
            'template' => 'NewMessage.twig',
        ];
        $templateData  = [
            'conversationId' => $conversationId,
            'receiver_name'  => $user->getDetail()->getNickName(),
            'sender_mail'    => $this->getSenderAddress('no_reply'),
        ];

        return $this->send($mailerOptions, $templateData);

    }

    /**
     * @param $insertionId
     *
     * @return bool
     * @throws ConfigElementNotFoundException
     * @throws Exception
     * @throws ORMException
     * @throws ServiceNotFoundException
     */
    //TODO: do this in corresponding plugin
    public function sendInsertionApprovedInfoMail($insertionId) {
        /** @var InsertionService $insertionService */
        $insertionService = Oforge()->Services()->get('insertion');

        /** @var Insertion $insertion */
        $insertion = $insertionService->getInsertionById($insertionId);

        /** @var User $user */
        $user     = $insertion->getUser();
        $userMail = $user->getEmail();

        $mailerOptions = [
            'to'       => [$userMail => $userMail],
            'from'     => 'no_reply',
            'subject'  => I18N::translate('mailer_subject_insertion_approved'),
            'template' => 'InsertionApproved.twig',
        ];
        $templateData  = [
            'insertionId'   => $insertionId,
            // TODO: add title 'insertionTitle'   => $insertion->getContent(),
            'receiver_name' => $user->getDetail()->getNickName(),
            'sender_mail'   => $this->getSenderAddress('no_reply'),
        ];

        return $this->send($mailerOptions, $templateData);
    }

    /**
     * @param $userId
     * @param $newResultsCount
     * @param $searchLink
     *
     * @return bool
     * @throws ConfigElementNotFoundException
     * @throws Exception
     * @throws ORMException
     * @throws ServiceNotFoundException
     */
    //TODO: do this in corresponding plugin
    public function sendNewSearchResultsInfoMail($userId, $newResultsCount, $searchLink) {
        /** @var  UserService $userService */
        $userService = Oforge()->Services()->get('frontend.user.management.user');

        /** @var User $user */
        $user     = $userService->getUserById($userId);
        $userMail = $user->getEmail();

        $mailerOptions = [
            'to'       => [$userMail => $userMail],
            'from'     => 'no_reply',
            'subject'  => I18N::translate('mailer_subject_new_search_results'),
            'template' => 'NewSearchResults.twig',
        ];
        $templateData  = [
            'resultCount'   => $newResultsCount,
            'searchLink'    => $searchLink,
            'sender_mail'   => $this->getSenderAddress('no_reply'),
            'receiver_name' => $user->getDetail()->getNickName(),

        ];

        return $this->send($mailerOptions, $templateData);
    }

    /**
     * @param User $user
     * @param Insertion $insertion
     *
     * @throws ConfigElementNotFoundException
     * @throws Exception
     * @throws ServiceNotFoundException
     */
    //TODO: do this in corresponding plugin
    public function sendInsertionCreateInfoMail(User $user, Insertion $insertion) {
        $userMail      = $user->getEmail();
        $mailerOptions = [
            'to'       => [$userMail => $userMail],
            'from'     => 'no_reply',
            'subject'  => I18N::translate('mailer_subject_insertion_created', 'Insertion was created'),
            'template' => 'InsertionCreated.twig',
        ];
        $templateData  = [
            'insertionId'    => $insertion->getId(),
            'insertionTitle' => $insertion->getContent()[0]->getTitle(),
            'receiver_name'  => $user->getDetail()->getNickName(),
            'sender_mail'    => $this->getSenderAddress('no_reply'),
        ];
        $this->send($mailerOptions, $templateData);
    }

}
