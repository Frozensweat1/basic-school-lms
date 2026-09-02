Hello {{ $recipient->recipient_name }},

{{ trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>'], ["\n", "\n", "\n", "\n\n", "\n"], $campaign->body)))) }}

--
{{ $campaign->school?->name ?: config('app.name') }}
This message was sent to {{ $recipient->email }}.
@if ($campaign->school?->email)
Questions? Reply to {{ $campaign->school->email }}.
@endif
