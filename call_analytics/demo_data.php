<?php
// Demo fixture for call_analytics/index.php, shown when FEATURE_CALL_ANALYTICS
// is off. Shape matches what a real `call_transcripts` row (once that table
// and its AssemblyAI ingestion job exist) is expected to provide per call:
// speaker-segmented turns with per-turn sentiment, and any action items
// extracted from the conversation.

return [
    [
        'id' => 1, 'date' => '2026-09-02 09:14', 'agent' => 'Anton', 'ext' => '222', 'caller' => '07911 223344',
        'duration_seconds' => 245,
        'snippet' => 'Caller thanked Anton for resolving the billing issue quickly.',
        'has_redacted_pii' => false,
        'turns' => [
            ['speaker' => 'Agent', 'sentiment' => 'neutral', 'text' => "Thanks for calling Gixo Support, this is Anton, how can I help today?"],
            ['speaker' => 'Caller', 'sentiment' => 'neutral', 'text' => "Hi, I wanted to follow up on the billing issue from last week."],
            ['speaker' => 'Agent', 'sentiment' => 'neutral', 'text' => "I can see that's been resolved on our end - the duplicate charge was refunded on Monday."],
            ['speaker' => 'Caller', 'sentiment' => 'positive', 'text' => "Oh perfect, I can see it now. Thank you so much for sorting that out!"],
            ['speaker' => 'Agent', 'sentiment' => 'neutral', 'text' => "You're welcome! Anything else I can help with?"],
            ['speaker' => 'Caller', 'sentiment' => 'positive', 'text' => "No, that was it. Have a great day!"],
        ],
        'action_items' => [],
    ],
    [
        'id' => 2, 'date' => '2026-09-02 08:52', 'agent' => 'Leo', 'ext' => '225', 'caller' => '07700 900123',
        'duration_seconds' => 312,
        'snippet' => 'Caller wants to cancel their subscription, frustrated with wait times.',
        'has_redacted_pii' => false,
        'turns' => [
            ['speaker' => 'Agent', 'sentiment' => 'neutral', 'text' => "Gixo Support, this is Leo, how can I help?"],
            ['speaker' => 'Caller', 'sentiment' => 'negative', 'text' => "I've been on hold for twenty minutes and I want to cancel my subscription."],
            ['speaker' => 'Agent', 'sentiment' => 'neutral', 'text' => "I'm really sorry about the wait. Can I ask what's prompting the cancellation?"],
            ['speaker' => 'Caller', 'sentiment' => 'negative', 'text' => "The service keeps dropping calls and nobody's fixed it in three weeks."],
            ['speaker' => 'Agent', 'sentiment' => 'neutral', 'text' => "That's not good enough, I understand the frustration. Let me escalate this to our network team right now."],
            ['speaker' => 'Caller', 'sentiment' => 'negative', 'text' => "Fine, but if it's not fixed by Friday I'm cancelling."],
        ],
        'action_items' => [
            ['text' => 'Escalate the recurring call-drop issue to the network team before Friday.', 'quote' => "Let me escalate this to our network team right now.", 'timestamp_seconds' => 47],
        ],
    ],
    [
        'id' => 3, 'date' => '2026-09-01 16:40', 'agent' => 'Maria', 'ext' => '300', 'caller' => '07811 556677',
        'duration_seconds' => 128,
        'snippet' => 'Routine question about an upcoming invoice date.',
        'has_redacted_pii' => false,
        'turns' => [
            ['speaker' => 'Agent', 'sentiment' => 'neutral', 'text' => "Hi, this is Maria from Gixo, how can I help?"],
            ['speaker' => 'Caller', 'sentiment' => 'neutral', 'text' => "Quick question - when does my next invoice go out?"],
            ['speaker' => 'Agent', 'sentiment' => 'neutral', 'text' => "Your billing cycle renews on the 15th, so you'll see it a couple of days before that."],
            ['speaker' => 'Caller', 'sentiment' => 'neutral', 'text' => "Got it, thanks for the quick answer."],
        ],
        'action_items' => [],
    ],
    [
        'id' => 4, 'date' => '2026-09-01 14:21', 'agent' => 'Anton', 'ext' => '222', 'caller' => '07911 223344',
        'duration_seconds' => 190,
        'snippet' => 'Caller asked about upgrading to a higher-tier plan.',
        'has_redacted_pii' => false,
        'turns' => [
            ['speaker' => 'Caller', 'sentiment' => 'neutral', 'text' => "Hi, I'd like to look at upgrading my plan for more lines."],
            ['speaker' => 'Agent', 'sentiment' => 'neutral', 'text' => "Happy to help - how many extra lines are you thinking?"],
            ['speaker' => 'Caller', 'sentiment' => 'neutral', 'text' => "Probably three more for the new starters."],
            ['speaker' => 'Agent', 'sentiment' => 'neutral', 'text' => "I can get that set up today, and I'll email you the updated pricing."],
            ['speaker' => 'Caller', 'sentiment' => 'positive', 'text' => "Perfect, thanks Anton."],
        ],
        'action_items' => [
            ['text' => 'Email updated pricing for 3 additional lines to the customer.', 'quote' => "I'll email you the updated pricing.", 'timestamp_seconds' => 95],
        ],
    ],
    [
        'id' => 5, 'date' => '2026-09-01 11:16', 'agent' => 'Leo', 'ext' => '225', 'caller' => '07700 900456',
        'duration_seconds' => 402,
        'snippet' => 'Complaint about a refund that has not arrived after two weeks.',
        'has_redacted_pii' => true,
        'turns' => [
            ['speaker' => 'Caller', 'sentiment' => 'negative', 'text' => "This is ### ####### calling - I was promised a refund two weeks ago and I still haven't seen it."],
            ['speaker' => 'Agent', 'sentiment' => 'neutral', 'text' => "I'm sorry about that delay, let me pull up your account."],
            ['speaker' => 'Agent', 'sentiment' => 'negative', 'text' => "I can see the refund was approved but it looks like it stalled in processing."],
            ['speaker' => 'Caller', 'sentiment' => 'negative', 'text' => "This is really disappointing, I've had to chase this three times now."],
            ['speaker' => 'Agent', 'sentiment' => 'neutral', 'text' => "You're right to be frustrated. I'm pushing this through manually today and I'll confirm once it's sent."],
        ],
        'action_items' => [
            ['text' => 'Manually push the stalled refund through and confirm to the customer once sent.', 'quote' => "I'm pushing this through manually today and I'll confirm once it's sent.", 'timestamp_seconds' => 210],
        ],
    ],
    [
        'id' => 6, 'date' => '2026-09-01 09:03', 'agent' => 'Maria', 'ext' => '300', 'caller' => '07811 556699',
        'duration_seconds' => 95,
        'snippet' => 'Short call, caller very happy with quick voicemail setup.',
        'has_redacted_pii' => false,
        'turns' => [
            ['speaker' => 'Caller', 'sentiment' => 'neutral', 'text' => "Hi, can you help me set up voicemail on my extension?"],
            ['speaker' => 'Agent', 'sentiment' => 'neutral', 'text' => "Sure, I've just enabled it - try dialling *97 to record your greeting."],
            ['speaker' => 'Caller', 'sentiment' => 'positive', 'text' => "That worked instantly, thank you!"],
        ],
        'action_items' => [],
    ],
    [
        'id' => 7, 'date' => '2026-08-31 17:46', 'agent' => 'Anton', 'ext' => '222', 'caller' => '07911 998877',
        'duration_seconds' => 210,
        'snippet' => 'General inquiry about international call rates.',
        'has_redacted_pii' => false,
        'turns' => [
            ['speaker' => 'Caller', 'sentiment' => 'neutral', 'text' => "What are your rates for calling the US?"],
            ['speaker' => 'Agent', 'sentiment' => 'neutral', 'text' => "It's 3p per minute on the standard plan, or included on the business bundle."],
            ['speaker' => 'Caller', 'sentiment' => 'neutral', 'text' => "Ok, I'll stick with what I have for now, thanks."],
        ],
        'action_items' => [],
    ],
    [
        'id' => 8, 'date' => '2026-08-31 13:53', 'agent' => 'Leo', 'ext' => '225', 'caller' => '07700 112233',
        'duration_seconds' => 150,
        'snippet' => 'Caller praised the new call quality after a recent update.',
        'has_redacted_pii' => false,
        'turns' => [
            ['speaker' => 'Caller', 'sentiment' => 'positive', 'text' => "Just wanted to say the call quality has been so much better this week."],
            ['speaker' => 'Agent', 'sentiment' => 'neutral', 'text' => "That's great to hear - we rolled out a network update on Monday."],
            ['speaker' => 'Caller', 'sentiment' => 'positive', 'text' => "Well it's working, keep it up!"],
        ],
        'action_items' => [],
    ],
    [
        'id' => 9, 'date' => '2026-08-31 10:28', 'agent' => 'Maria', 'ext' => '300', 'caller' => '07811 334455',
        'duration_seconds' => 365,
        'snippet' => 'Caller asked to escalate to a manager over a billing dispute.',
        'has_redacted_pii' => false,
        'turns' => [
            ['speaker' => 'Caller', 'sentiment' => 'negative', 'text' => "I need to speak to a manager, this billing dispute has gone on too long."],
            ['speaker' => 'Agent', 'sentiment' => 'neutral', 'text' => "I understand, let me see what I can do before escalating."],
            ['speaker' => 'Caller', 'sentiment' => 'negative', 'text' => "I've already been told that twice. I want to escalate now."],
            ['speaker' => 'Agent', 'sentiment' => 'neutral', 'text' => "Understood - I'm logging this for the duty manager to call you back within the hour."],
        ],
        'action_items' => [
            ['text' => 'Log the billing dispute for the duty manager to call the customer back within the hour.', 'quote' => "I'm logging this for the duty manager to call you back within the hour.", 'timestamp_seconds' => 178],
        ],
    ],
    [
        'id' => 10, 'date' => '2026-08-30 15:32', 'agent' => 'Anton', 'ext' => '222', 'caller' => '07911 223344',
        'duration_seconds' => 88,
        'snippet' => 'Routine password reset request.',
        'has_redacted_pii' => false,
        'turns' => [
            ['speaker' => 'Caller', 'sentiment' => 'neutral', 'text' => "I'm locked out of the portal, can you reset my password?"],
            ['speaker' => 'Agent', 'sentiment' => 'neutral', 'text' => "Done - you'll get a reset link by email in the next minute."],
            ['speaker' => 'Caller', 'sentiment' => 'neutral', 'text' => "Got it, thanks."],
        ],
        'action_items' => [],
    ],
    [
        'id' => 11, 'date' => '2026-08-30 12:14', 'agent' => 'Leo', 'ext' => '225', 'caller' => '07700 900123',
        'duration_seconds' => 175,
        'snippet' => 'Caller satisfied after a quick fix to call forwarding.',
        'has_redacted_pii' => false,
        'turns' => [
            ['speaker' => 'Caller', 'sentiment' => 'neutral', 'text' => "My calls aren't forwarding to my mobile anymore."],
            ['speaker' => 'Agent', 'sentiment' => 'neutral', 'text' => "Looks like the rule got disabled in an update - I've turned it back on."],
            ['speaker' => 'Caller', 'sentiment' => 'positive', 'text' => "Perfect, that fixed it, thank you!"],
        ],
        'action_items' => [],
    ],
    [
        'id' => 12, 'date' => '2026-08-30 09:08', 'agent' => 'Maria', 'ext' => '300', 'caller' => '07811 556677',
        'duration_seconds' => 140,
        'snippet' => 'Routine question about adding a new extension.',
        'has_redacted_pii' => false,
        'turns' => [
            ['speaker' => 'Caller', 'sentiment' => 'neutral', 'text' => "How do I add a new extension for a new hire?"],
            ['speaker' => 'Agent', 'sentiment' => 'neutral', 'text' => "I can add that for you now - what name should I use for the extension?"],
            ['speaker' => 'Caller', 'sentiment' => 'neutral', 'text' => "Sophie Turner, starting Monday."],
            ['speaker' => 'Agent', 'sentiment' => 'neutral', 'text' => "All set, extension 231 is ready for her."],
        ],
        'action_items' => [],
    ],
];
