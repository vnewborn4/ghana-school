<?php
/**
 * English strings for the student academy. This file is the reference:
 * every key must exist here, because other languages fall back to it.
 *
 * Scope note: only the student portal is translated. English is the primary
 * language throughout; a Ghanaian language is there to clarify an instruction
 * for a learner, not to replace English.
 *
 * Not translated, deliberately:
 *   - the sponsor portal and sponsor updates -- donors read English
 *   - the public marketing pages
 *   - the teacher and admin portals -- staff work in English
 *   - programming keywords, HTML tags and CSS properties, anywhere
 */
return [
    // Common
    'app.academy'            => 'Academy',
    'common.sign_in'         => 'Sign in',
    'common.sign_out'        => 'Sign out',
    'common.save'            => 'Save',
    'common.saved'           => 'Saved',
    'common.cancel'          => 'Cancel',
    'common.continue'        => 'Continue',
    'common.back'            => 'Back',
    'common.help'            => 'Help',
    'common.yes'             => 'Yes',
    'common.no'              => 'No',
    'common.language'        => 'Language',
    'common.required'        => 'This is needed.',
    'common.problem'         => 'Something went wrong. Please tell your teacher.',

    // Sign in
    'login.title'            => 'Sign in to the Academy',
    'login.intro'            => 'Use the username and PIN your teacher gave you.',
    'login.username'         => 'Username',
    'login.pin'              => 'PIN',
    'login.submit'           => 'Sign in',
    'login.error'            => 'That username or PIN is not right. Try again.',
    'login.locked'           => 'This account is locked. Please ask your teacher to unlock it.',
    'login.inactive'         => 'This account is not active. Please ask your teacher.',
    'login.help_note'        => 'Forgotten your PIN? Ask your teacher. Never share your PIN with another student.',
    'login.shared_computer'  => 'Sharing this computer? Always sign out when you finish.',

    // Choosing a new PIN
    'pin.title'              => 'Choose your own PIN',
    'pin.intro'              => 'Pick a PIN only you know. You will use it every time you sign in.',
    'pin.new'                => 'New PIN',
    'pin.confirm'            => 'Type your new PIN again',
    'pin.submit'             => 'Save my PIN',
    'pin.mismatch'           => 'Those two PINs are not the same. Try again.',
    'pin.too_short'          => 'Your PIN needs at least 4 numbers.',
    'pin.too_simple'         => 'Please choose a PIN that is not 1234 or all the same number.',
    'pin.changed'            => 'Your new PIN is saved.',

    // Dashboard
    'dash.greeting'          => 'Hello, {name}',
    'dash.subtitle'          => 'Here is your work.',
    'dash.my_lessons'        => 'My lessons',
    'dash.my_page'           => 'My page',
    'dash.my_badges'         => 'My badges',
    'dash.no_lessons'        => 'Your teacher has not added any lessons yet.',
    'dash.open'              => 'Open',
    'dash.to_do'             => 'To do',
    'dash.waiting'           => 'Waiting for your teacher',
    'dash.done'              => 'Done',
    'dash.needs_work'        => 'Try again',
    'dash.has_feedback'      => 'Your teacher wrote to you',

    // Assignments
    'asg.brief'              => 'What to do',
    'asg.open_tool'          => 'Open the activity',
    'asg.your_work'          => 'Your work',
    'asg.reflection_hint'    => 'Write your answer here in your own words.',
    'asg.code_hint'          => 'Paste your code here.',
    'asg.upload'             => 'Choose a file',
    'asg.upload_hint'        => 'A screenshot or a project file from the activity.',
    'asg.site_hint'          => 'This lesson is about your own page. Open My page, do the work there, then come back and tell your teacher you are ready.',
    'asg.save_draft'         => 'Save for later',
    'asg.submit'             => 'Give to my teacher',
    'asg.resubmit'           => 'Send it again',
    'asg.submitted_on'       => 'You sent this on {date}.',
    'asg.teacher_feedback'   => 'What your teacher said',
    'asg.draft_saved'        => 'Saved. You can come back to it later.',
    'asg.submitted'          => 'Sent to your teacher. Well done.',
    'asg.file_too_big'       => 'That file is too big. Ask your teacher for help.',
    'asg.file_type'          => 'That kind of file is not allowed here.',

    // My page (student web space)
    'site.title'             => 'My page',
    'site.intro'             => 'This is your own page. Change it, look at it, and when you are proud of it, ask your teacher to put it online.',
    'site.address'           => 'Your page address',
    'site.tab_html'          => 'Content (HTML)',
    'site.tab_css'           => 'Style (CSS)',
    'site.tab_js'            => 'Code (JavaScript)',
    'site.preview'           => 'Preview',
    'site.refresh_preview'   => 'Refresh preview',
    'site.save'              => 'Save my work',
    'site.request_review'    => 'Ask my teacher to check it',
    'site.requested'         => 'Your teacher has been asked to check your page.',
    'site.space_used'        => 'Space used: {used} of {total}',
    'site.status.draft'      => 'Only you and your teacher can see this.',
    'site.status.pending'    => 'Your teacher is checking it.',
    'site.status.published'  => 'Your page is online.',
    'site.status.unpublished'=> 'Your page is not online at the moment.',
    'site.status.suspended'  => 'Your page is paused. Please talk to your teacher.',
    'site.safety_title'      => 'Keep yourself safe',
    'site.safety_reminder'   => 'Never put your full name, your address, your phone number, or the name of your school on your page.',
    'site.saved'             => 'Your work is saved.',

    // Badges
    'badge.title'            => 'My badges',
    'badge.none'             => 'Finish a lesson to earn your first badge.',
    'badge.earned_on'        => 'Earned {date}',
];
