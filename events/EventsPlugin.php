<?php
namespace Craft;

/**
 * Events plugin class
 */
class EventsPlugin extends BasePlugin
{
	public function getName()
	{
	    return 'Events';
	}

	public function getVersion()
	{
	    return '1.0';
	}

	public function getDeveloper()
	{
	    return 'Pixel & Tonic';
	}

	public function getDeveloperUrl()
	{
	    return 'http://pixelandtonic.com';
	}

	public function hasCpSection()
	{
		return true;
	}

	public function registerCpRoutes()
	{
		return array(
			'events/calendars'                                     => array('action' => 'events/calendarIndex'),
			'events/calendars/new'                                 => array('action' => 'events/editCalendar'),
			'events/calendars/(?P<calendarId>\d+)'                 => array('action' => 'events/editCalendar'),
			'events'                                               => array('action' => 'events/eventIndex'),
			'events/(?P<calendarHandle>{handle})/new'              => array('action' => 'events/editEvent'),
			'events/(?P<calendarHandle>{handle})/(?P<eventId>\d+)' => array('action' => 'events/editEvent'),
		);
	}
}
