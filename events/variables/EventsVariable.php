<?php
namespace Craft;

class EventsVariable
{
	function events()
	{
		return craft()->elements->getCriteria('Events_Event');
	}
}
