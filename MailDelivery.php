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
        $notifications = \Depage\Notifications\Notification::loadByDelivery($this->pdo, "mail");

        $task = \Depage\Tasks\Task::loadOrCreate($this->pdo, "Depage Notification Delivery");

        $init = $task->addSubtask("init", "\$delivery = %s;", [$this]);
        foreach ($notifications as $n) {
            $task->addSubtask("delivering", "\$delivery->deliver(%d);", [$n->id], $init);
        }
        if (count($notifications) > 0) {
            $task->addSubtask("delivering", "\$delivery->addDeliveryTask();", [], $init);
        }
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
    public function deliver($id)
    {
        $notifications = \Depage\Notifications\Notification::loadById($this->pdo, $id);

        foreach($notifications as $n) {
            if (!empty($n->uid)) {
                $to = \Depage\Auth\User::loadById($this->pdo, $n->uid)->email;
            } else if (!empty($n->sid)) {
                $to = \Depage\Auth\User::loadBySid($this->pdo, $n->sid)->email;
            } else {
                continue;
            }

            $url = parse_url(DEPAGE_BASE);

            $subject = $url['host'] . " . " . $n->title;
            $text = "";
            $text .= sprintf(_("You received a new notification from %s:"), $url['host']) . "\n\n";
            $text .= $n->message . "\n\n";

            if (!empty($n->options["link"])) {
                $text .= $n->options["link"] . "\n\n";
            }

            $text .= "--\n";
            $text .= _("Your faithful servant on") . "\n";
            $text .= DEPAGE_BASE . "\n";

            $mail = new \Depage\Mail\Mail($this->from);
            $mail
                ->setSubject($subject)
                ->setText($text)
                ->send($to);

            $n->delivered("mail");
        }

    }
    // }}}
}

// vim:set ft=php sw=4 sts=4 fdm=marker et :
