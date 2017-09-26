<?php
/**
 * @file    MailDelivery.php
 *
 * description
 *
 * copyright (c) 2017 Frank Hellenkamp [jonas@depage.net]
 *
 * @author    Frank Hellenkamp [jonas@depage.net]
 */

namespace Depage\Notifications;

/**
 * @brief MailDelivery
 * Class MailDelivery
 */
class MailDelivery
{
    // {{{ __construct()
    /**
     * @brief __construct
     *
     * @param mixed $pdo
     * @return void
     **/
    public function __construct($pdo, $from)
    {
        $this->pdo = $pdo;
        $this->from = $from;
    }
    // }}}
    // {{{ addDeliveryTask()
    /**
     * @brief addDeliveryTask
     *
     * @param mixed
     * @return void
     **/
    public function addDeliveryTask()
    {
        $task = \Depage\Tasks\Task::loadOrCreate($this->pdo, "Depage Notification Delivery");

        $task->addSubtask("delivering", "\$d = %s; \$d->deliver();", [$this]);
        $task->begin();
    }
    // }}}
    // {{{ deliver()
    /**
     * @brief deliver
     *
     * @param mixed
     * @return void
     **/
    public function deliver()
    {
        $notifications = \Depage\Notifications\Notification::loadByDelivery($this->pdo, "mail", 10);

        foreach($notifications as $n) {
            if (!empty($n->uid)) {
                $user = \Depage\Auth\User::loadById($this->pdo, $n->uid);
            } else if (!empty($n->sid)) {
                $user = \Depage\Auth\User::loadBySid($this->pdo, $n->sid);
            } else {
                continue;
            }
            $to = $user->email;

            $opt = $n->options;

            $mail = new \Depage\Mail\Mail($this->from);
            $mail->setSubject($n->title);

            if (isset($opt['mail'])) {
                if (is_a($opt['mail'], '\Depage\Html\Html')) {
                    $opt['mail']->addArg("title", $n->title);
                    $opt['mail']->addArg("message", $n->message);
                    $opt['mail']->addArg("user", $n->message);
                    if (isset($opt['link'])) {
                        $opt['mail']->addArg("link", $opt['link']);
                    }
                }

                $mail->setHtmlText($opt['mail']);
            } else {
                $url = parse_url("http://depage.net");

                $text = "";
                $text .= sprintf(_("You received a new notification from %s:"), $url['host']) . "\n\n";
                $text .= $n->message . "\n\n";

                if (!empty($n->options["link"])) {
                    $text .= $n->options["link"] . "\n\n";
                }

                $text .= "--\n";
                $text .= _("Your faithful servant on") . "\n";
                $text .= DEPAGE_BASE . "\n";

                $mail->setText($text);
            }


            $mail->send($to);

            $n->delivered("mail");
        }

        if (count($notifications) > 0) {
            $this->addDeliveryTask();
        }
    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
