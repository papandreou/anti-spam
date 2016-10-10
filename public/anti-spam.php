<?php

require_once '../vendor/autoload.php';
require_once '../accounts.php';
require_once '../rules.php';

use Fetch\Message;
use Fetch\Server;

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

    if (!$server->hasMailBox($inboxes[$id]['prefix'] . $inboxes[$id]['folder'])) {
        $server->createMailBox($inboxes[$id]['prefix'] . $inboxes[$id]['folder']);
    }

    $unreadMessages[$id] = $server->search('UNSEEN');
}

foreach ($unreadMessages as $id => $messages) {
    echo "<br>\n<b>Now processing:</b> " . $id . "<br>\n";
    file_put_contents("../logs/spam.log", "\nNow processing: " . $id . "\n", FILE_APPEND);
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

        //$spam = isRecruiterSpam($rules, $message) ? '' : 'not';
        $whitelisted = isWhitelisted($whitelistRules, $message) ? true : false;
        
        if ($whitelisted == true) {
            echo "<span style=\"background-color: #73ca73;\">Subject for {$i}: \"{$message->getSubject()}\" was found in <b>whitelist</b> rules.</span><br>\n";
            //file_put_contents($inboxes[$id]['logfile'], "Whitelist: {$i}: \"{$message->getSubject()}\"\n", FILE_APPEND);
            //echo $msg;
        }
        elseif ($whitelisted == false) {
            $spam = isRecruiterSpam($rules, $message) ? true : false;
            
            if ($whitelisted == false && $spam == true) {

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

                //$result = $mailer->send($reply);
                $result = "test";
                if ($result) {
                    $message->moveToMailBox($inboxes[$id]['prefix'] . $inboxes[$id]['folder']);
                    echo "<span style=\"background-color: #ff6d6d;\">Subject for: \"{$message->getSubject()}\" is <b>probably</b> spam. Sent auto-reply to sender, and moved mail to \"autoreplied\"-folder.</span><br>\n";
                    file_put_contents($inboxes[$id]['logfile'], "=> Rejected: \"{$message->getSubject()}\"\n\n", FILE_APPEND);
                    //echo $msg;
                }
            }
            elseif ($whitelisted == false && $spam == false) {
                echo "<span style=\"background-color: yellow;\">Subject for: \"{$message->getSubject()}\" did not match any spam rules.</span><br>\n";
                file_put_contents($inboxes[$id]['logfile'], "=> Accepted: \"{$message->getSubject()}\"\n\n", FILE_APPEND);
                //echo $msg;
            }
        }
    }
}

function isRecruiterSpam($rules, Message $message)
{
    $sum = 0;
    file_put_contents("../logs/spam.log", "### SpamCheck: ###\nSubject: " . $message->getSubject() . "\n", FILE_APPEND);
        
    $msgBody = $message->getMessageBody();
    $msgBody = str_replace("&#230;", "æ", $msgBody);
    $msgBody = str_replace("&#198;", "Æ", $msgBody);
    $msgBody = str_replace("&#248;", "ø", $msgBody);
    $msgBody = str_replace("&#216;", "Ø", $msgBody);
    $msgBody = str_replace("&#229;", "å", $msgBody);
    $msgBody = str_replace("&#197;", "Å", $msgBody);
    
    //file_put_contents("../logs/spam.log", $message->getMessageBody() . "\n", FILE_APPEND);
    //file_put_contents("../logs/spam.log", $msgBody . "\n", FILE_APPEND);
    
    foreach ($rules as $rule) {
        if (isset($rule['contains'])) {
            if (preg_match($rule['contains'], $message->getSubject())
                || preg_match($rule['contains'], $msgBody)
                //|| preg_match($rule['contains'], $message->getMessageBody())
            ) {
                $sum += $rule['points'];
                file_put_contents("../logs/spam.log", "Rule (" . $rule['points'] . "): " . $rule['contains'] . " (CONTAIN)\n", FILE_APPEND);
            }
        } else {
            if (isset($rule['from'])) {
                if (preg_match($rule['from'], $message->getOverview()->from)
                ) {
                    $sum += $rule['points'];
                    file_put_contents("../logs/spam.log", "Rule (" . $rule['points'] . "): " . $rule['from'] . " (FROM)\n", FILE_APPEND);
                }
            }
        }
        if ($sum > 99) {
            file_put_contents("../logs/spam.log", "Total spam-score: $sum\n", FILE_APPEND);
            return true;
        }
    }
    
    file_put_contents("../logs/spam.log", "Total spam-score: $sum\n", FILE_APPEND);
    return false;
}

function isWhitelisted($rules, Message $message) {
    foreach ($rules as $rule) {
        if (isset($rule['contains'])) {
            if (preg_match($rule['contains'], $message->getSubject())
                || preg_match($rule['contains'], $message->getMessageBody())
            ) {
                file_put_contents("../logs/spam.log", "### Whitelist: ###\nSubject: " . $message->getSubject() . "\nOn contain-rule: " . $rule['contains'] . "\n\n", FILE_APPEND);
                return true;
            }
        } else {
            if (isset($rule['from'])) {
                if (preg_match($rule['from'], $message->getOverview()->from)
                ) {
                    file_put_contents("../logs/spam.log", "### Whitelist: ###\nSubject: " . $message->getSubject() . "\nOn from-rule: " . $rule['from'] . "\n\n", FILE_APPEND);
                    return true;
                }
            }
        }
    }
    return false;
}
