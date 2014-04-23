<?php
namespace Craft;

/**
 * Events - Event model
 */
class Events_EventModel extends BaseElementModel
{
	protected $elementType = 'Events_Event';

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array_merge(parent::defineAttributes(), array(
			'calendarId' => AttributeType::Number,
			'startDate'  => AttributeType::DateTime,
			'endDate'    => AttributeType::DateTime,
		));
	}

	/**
	 * Returns whether the current user can edit the element.
	 *
	 * @return bool
	 */
	public function isEditable()
	{
		return true;
	}

	/**
	 * Returns the element's CP edit URL.
	 *
	 * @return string|false
	 */
	public function getCpEditUrl()
	{
		$calendar = $this->getCalendar();

		if ($calendar)
		{
			return UrlHelper::getCpUrl('events/'.$calendar->handle.'/'.$this->id);
		}
	}

	/**
	 * Returns the field layout used by this element.
	 *
	 * @return FieldLayoutModel|null
	 */
	public function getFieldLayout()
	{
		$calendar = $this->getCalendar();

		if ($calendar)
		{
			return $calendar->getFieldLayout();
		}
	}

	/**
	 * Returns the event's calendar.
	 *
	 * @return Events_CalendarModel|null
	 */
	public function getCalendar()
	{
		if ($this->calendarId)
		{
			return craft()->events_calendars->getCalendarById($this->calendarId);
		}
	}
}
