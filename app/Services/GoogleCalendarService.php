<?php

/**
 * GoogleCalendarService
 * 
 * @author Achal Sharma
 * @package App\Services
 * @version 1.0.1
 */

/*
    * Author: Sanjay Kumar
    * Email: sanjay.jangid@dgtoohl.com
    * Date: 2024-06-10
    * Copyright: Sanjay Kumar
    * Description: Service to interact with Google Calendar API
 */


namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use App\Models\GoogleCalender;
use Exception;
use Carbon\Carbon;
use GuzzleHttp\Client as GuzzleClient;

class GoogleCalendarService
{
    /**
     * @var Client
     */
    protected Client $client;

    /**
     * GoogleCalendarService constructor.
     */
    public function __construct()
    {
        $this->client = new Client();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect'));
        $this->client->setScopes([Calendar::CALENDAR]);
        $this->client->setAccessType('offline'); // IMPORTANT
        $this->client->setPrompt('consent');     // IMPORTANT
        
        // Fix SSL certificate issue on Windows/WAMP
        $httpClient = new GuzzleClient([
            'verify' => false  // Disable SSL verification for development
        ]);
        $this->client->setHttpClient($httpClient);
    }

    /* ===============================
       AUTH
    =============================== */

    /**
     * Generate first-time Google login authorization URL.
     *
     * @return string
     */
    public function getAuthUrl(): string
    {
        return $this->client->createAuthUrl();
    }

    /**
     * Save OAuth access token after Google authorization callback.
     *
     * @param string $code
     * @param int $userId
     * @return void
     * @throws Exception
     */
    public function saveToken(string $code, int $userId = 1): void
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            throw new Exception('Google authentication failed');
        }

        GoogleCalender::updateOrCreate(
            ['user_id' => $userId],
            ['token' => json_encode($token)]
        );
    }

    /* ===============================
       CALENDAR CLIENT (AUTO LOGIN + REFRESH)
    =============================== */

    /**
     * Get initialized Google Calendar client instance with auto-login and token refresh.
     *
     * @param int $userId
     * @return Calendar
     * @throws Exception
     */
    protected function getCalendar(int $userId): Calendar
    {
        $googleCalendar = GoogleCalender::where('user_id', $userId)->first();

        if (!$googleCalendar) {
            throw new Exception('Google account not connected.');
        }

        $token = json_decode($googleCalendar->token, true);
     
        //  Set existing token
        $this->client->setAccessToken($token);

        //  Refresh token if expired
        if ($this->client->isAccessTokenExpired()) {
            if (!isset($token['refresh_token'])) {
                throw new Exception('Refresh token missing. Please login again.');
            }

            $newToken = $this->client->fetchAccessTokenWithRefreshToken(
                $token['refresh_token']
            );

            if (isset($newToken['error'])) {
                throw new Exception('Failed to refresh Google access token.');
            }
            
            //  Keep refresh_token safe
            $newToken['refresh_token'] = $token['refresh_token'];

            //  Save updated token
            $googleCalendar->update([
                'token' => json_encode($newToken)
            ]);
           
            //  Set new token
            $this->client->setAccessToken($newToken);
        }

        return new Calendar($this->client);
    }

   
    /**
     * Create event in Google Calendar (legacy implementation).
     *
     * @param array $data
     * @param int $userId
     * @return array
     * @throws Exception
     */
    public function createEventOld(array $data, int $userId = 1): array
    {        
        $calendar = $this->getCalendar($userId);

        $event = new Event([
            'summary'     => $data['summary'],
            'description' => $data['description'] ?? '',
            'start' => [
                'dateTime' => Carbon::parse($data['start'])->toIso8601String(), // Y-m-d H:i:s
                'timeZone' => 'Asia/Kolkata',
            ],
            'end' => [
                'dateTime' => Carbon::parse($data['end'])->toIso8601String(),
                'timeZone' => 'Asia/Kolkata',
            ],
            'attendees' => $this->formatAttendees($data['attendees'] ?? []),
        ]);

        $createdEvent = $calendar->events->insert('primary', $event);

        return [
            'event_id'   => $createdEvent->getId(),
            'html_link'  => $createdEvent->getHtmlLink(),
        ];
    }

    /**
     * Create event in Google Calendar with optional Google Meet link.
     *
     * @param array $data
     * @param int $userId
     * @return array
     * @throws Exception
     */
    public function createEvent(array $data, int $userId = 1): array
    {
        $calendar = $this->getCalendar($userId);

        $event = new Event([
            'summary'     => $data['summary'],
            'description' => $data['description'] ?? '',
            'start' => [
                'dateTime' => Carbon::parse($data['start'])->toIso8601String(),
                'timeZone' => 'Asia/Kolkata',
            ],
            'end' => [
                'dateTime' => Carbon::parse($data['end'])->toIso8601String(),
                'timeZone' => 'Asia/Kolkata',
            ],
            'attendees' => $this->formatAttendees($data['attendees'] ?? []),

            // OPTIONAL: Google Meet link
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => uniqid(),
                ],
            ],
        ]);

        $created = $calendar->events->insert(
            'primary',
            $event,
            [
                'sendUpdates' => 'all', // notify attendees
                'conferenceDataVersion' => 1,    // required for Google Meet
            ]
        );

        return [
            'event_id'     => $created->getId(),
            'html_link'    => $created->getHtmlLink(),
            'meet_link'    => optional($created->getConferenceData())
                                ->getEntryPoints()[0]
                                ->getUri() ?? null,
        ];
    }


    /**
     * Format email addresses into Google Calendar attendee structures.
     *
     * @param array $emails
     * @return array
     */
    protected function formatAttendees(array $emails = []): array
    {
        $attendees = [];

        foreach ($emails as $email) {
            if (!empty($email)) {
                $attendees[] = ['email' => $email];
            }
        }

        return $attendees;
    }


    /**
     * Update an existing Google Calendar event.
     *
     * @param string $eventId
     * @param array $data
     * @param int $userId
     * @return array
     * @throws Exception
     */
    public function updateEvent(string $eventId, array $data, int $userId = 1): array
    {
        $calendar = $this->getCalendar($userId);
        $event = $calendar->events->get('primary', $eventId);

        if (isset($data['summary'])) {
            $event->setSummary($data['summary']);
        }

        if (isset($data['description'])) {
            $event->setDescription($data['description']);
        }

        if (isset($data['start'], $data['end'])) {
            $startDateTime = new \Google\Service\Calendar\EventDateTime();
            $startDateTime->setDateTime(Carbon::parse($data['start'])->toIso8601String());
            $startDateTime->setTimeZone('Asia/Kolkata');
            $event->setStart($startDateTime);

            $endDateTime = new \Google\Service\Calendar\EventDateTime();
            $endDateTime->setDateTime(Carbon::parse($data['end'])->toIso8601String());
            $endDateTime->setTimeZone('Asia/Kolkata');
            $event->setEnd($endDateTime);
        }

        $updatedEvent = $calendar->events->update('primary', $eventId, $event);

        return [
            'event_id'  => $updatedEvent->getId(),
            'html_link' => $updatedEvent->getHtmlLink(),
        ];
    }

    /**
     * Delete an event from Google Calendar.
     *
     * @param string $eventId
     * @param int $userId
     * @return bool
     * @throws Exception
     */
    public function deleteEvent(string $eventId, int $userId = 1): bool
    {
        $calendar = $this->getCalendar($userId);
        $calendar->events->delete('primary', $eventId);
        return true;
    }
}