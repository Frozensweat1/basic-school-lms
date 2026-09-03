<?php

namespace App\Services\Sms;

class SmsMessageMetrics
{
    private const GSM_BASIC = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";

    private const GSM_EXTENDED = "^{}\\[~]|€\f";

    public function clean(string $message): string
    {
        $message = str_ireplace(
            ['<br>', '<br/>', '<br />', '</p>', '</div>', '</li>'],
            ["\n", "\n", "\n", "\n", "\n", "\n"],
            $message,
        );
        $message = html_entity_decode(strip_tags($message), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $message = str_replace(["\r\n", "\r"], "\n", $message);
        $message = (string) preg_replace('/[^\P{C}\n\t]/u', '', $message);
        $message = (string) preg_replace('/[ \t]+\n/u', "\n", $message);
        $message = (string) preg_replace('/\n[ \t]+/u', "\n", $message);
        $message = (string) preg_replace('/\n{3,}/u', "\n\n", $message);

        return trim($message);
    }

    /**
     * @return array{encoding:string,character_count:int,billing_units:int,segment_count:int}
     */
    public function analyse(string $message): array
    {
        $message = $this->clean($message);
        $characters = mb_str_split($message);
        $billingUnits = 0;
        $isGsm7 = true;

        foreach ($characters as $character) {
            if (str_contains(self::GSM_BASIC, $character)) {
                $billingUnits++;

                continue;
            }

            if (str_contains(self::GSM_EXTENDED, $character)) {
                $billingUnits += 2;

                continue;
            }

            $isGsm7 = false;
            break;
        }

        if (! $isGsm7) {
            $billingUnits = count($characters);
        }

        $singleSegmentLimit = $isGsm7 ? 160 : 70;
        $multipartSegmentLimit = $isGsm7 ? 153 : 67;
        $segmentCount = match (true) {
            $billingUnits === 0 => 0,
            $billingUnits <= $singleSegmentLimit => 1,
            default => (int) ceil($billingUnits / $multipartSegmentLimit),
        };

        return [
            'encoding' => $isGsm7 ? 'GSM-7' : 'UCS-2',
            'character_count' => count($characters),
            'billing_units' => $billingUnits,
            'segment_count' => $segmentCount,
        ];
    }
}
