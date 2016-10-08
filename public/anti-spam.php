<?php

require_once '../vendor/autoload.php';

use Fetch\Message;
use Fetch\Server;

$inboxes = [
    'test@flygenring.it' => [
        'username' => 'test@flygenring.it',
        'password' => 'testtest',
        'aliases' => ['test@flygenring.it'],
        'smtp' => 'send.one.com',
        'smtp_port' => '587',
        'imap' => 'imap.one.com',
        'starttls' => true
    ] /*,
    'primary@mydomain.com' => [
        'username' => 'someusername',
        'password' => 'password',
        'aliases' => ['alias@mydomain.com'],
        'smtp' => 'smtp.fastmail.com',
        'smtp_port' => '587',
        'imap' => 'imap.fastmail.com',
        'starttls' => true
    ] */
];

$rules = [
    ['contains' => 'finding IT opportunities', 'points' => 100],
    ['contains' => 'PHP specialists?', 'points' => 80],
    ['contains' => 'startups?', 'points' => 10],
    ['contains' => 'saw your profile on GitHub', 'points' => 50],
    ['contains' => 'explore-group\.com', 'points' => 100],
    ['from' => '@explorerec\.com', 'points' => 100],
    ['contains' => 'new position', 'points' => 20],
    ['contains' => 'urgent(ly)? need', 'points' => 30],
    ['contains' => 'huge plus', 'points' => 15],
    ['contains' => 'full-stack developer', 'points' => 30],
    ['contains' => 'interviews?', 'points' => 20],
    ['contains' => 'CV', 'points' => 60],
    ['contains' => 'skills', 'points' => 10],
    ['contains' => 'candidates?', 'points' => 20],
];

$whitelistRules = [
    ['contains' => '/sent via our-little-app/i'],
];

$points = [];
foreach ($rules as $key => &$rule) {
    $points[$key] = $rule['points'];
    if (isset($rule['contains'])) {
        $rule['contains'] = '/' . $rule['contains'] . '/i';
    }
    if (isset($rule['from'])) {
        $rule['from'] = '/' . $rule['from'] . '/i';
    }
}
array_multisort($points, SORT_DESC, $rules);

$unreadMessages = [];
foreach ($inboxes as $id => $inbox) {
    $server = new Server($inbox['imap'], 993);
    $server->setAuthentication($inbox['username'], $inbox['password']);

    if (!$server->hasMailBox('INBOX.autoreplied')) {
        $server->createMailBox('INBOX.autoreplied');
    }

    $unreadMessages[$id] = $server->search('UNSEEN');
}

foreach ($unreadMessages as $id => $messages) {
    echo "Now processing: " . $id . "<br>";
    if (!empty($messages)) {
        $mailer = Swift_Mailer::newInstance(
            Swift_SmtpTransport::newInstance(
                $inboxes[$id]['smtp'], $inboxes[$id]['smtp_port'],
                (isset($inboxes[$id]['starttls'])) ? 'tls' : null
            )
                ->setUsername($inboxes[$id]['username'])
                ->setPassword($inboxes[$id]['password'])
                ->setStreamOptions(
                    [
                        'ssl' => [
                            'allow_self_signed' => true,
                            'verify_peer' => false,
                        ],
                    ]
                )

        );
    } else {
        continue;
    }
    /**
     * @var Message $message
     */
    foreach ($messages as $i => $message) {

        if (!isWhitelisted($whitelistRules, $message)
            && isRecruiterSpam($rules, $message)
        ) {

            $message->setFlag(Message::FLAG_SEEN);

            $potentialSender = $message->getAddresses('to')[0]['address'];
            $sender = (in_array($potentialSender, $inboxes[$id]['aliases']))
                ? $potentialSender : $inboxes[$id]['aliases'][0];

            $reply = Swift_Message::newInstance('Re: ' . $message->getSubject())
                ->setFrom($message->getAddresses('to')[0]['address'])
                ->setTo($message->getAddresses('from')['address'])
                ->setBody(
                    file_get_contents('../templates/recruiter.html'),
                    'text/html'
                );

            $result = $mailer->send($reply);
	    if ($result) {
          $message->moveToMailBox('INBOX.autoreplied');
		      $spam = isRecruiterSpam($rules, $message) ? '' : 'not';
	        echo "Subject for {$i}: {$message->getSubject()} is probably {$spam} recruiter spam. Sent auto-reply to sender, and moved mail to \"autoreplied\"-folder.\n<br>";
	    }
        }

    }
}

function isRecruiterSpam($rules, Message $message)
{
    $sum = 0;
    foreach ($rules as $rule) {
        if (isset($rule['contains'])) {
            if (preg_match($rule['contains'], $message->getSubject())
                || preg_match($rule['contains'], $message->getHtmlBody())
            ) {
                $sum += $rule['points'];
            }
        } else {
            if (isset($rule['from'])) {
                if (preg_match($rule['from'], $message->getOverview()->from)
                ) {
                    $sum += $rule['points'];
                }
            }
        }
        if ($sum > 99) {
            return true;
        }
    }

    return false;
}

function isWhitelisted($rules, Message $message) {
    foreach ($rules as $rule) {
        if (isset($rule['contains'])) {
            if (preg_match($rule['contains'], $message->getSubject())
                || preg_match($rule['contains'], $message->getHtmlBody())
            ) {
                return true;
            }
        } else {
            if (isset($rule['from'])) {
                if (preg_match($rule['from'], $message->getOverview()->from)
                ) {
                    return true;
                }
            }
        }
    }
    return false;
}
