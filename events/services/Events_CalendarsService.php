<?php
namespace Craft;

/**
 * Calendars service
 */
class Events_CalendarsService extends BaseApplicationComponent
{
	private $_allCalendarIds;
	private $_calendarsById;
	private $_fetchedAllCalendars = false;

	/**
	 * Returns all of the calendar IDs.
	 *
	 * @return array
	 */
	public function getAllCalendarIds()
	{
		if (!isset($this->_allCalendarIds))
		{
			if ($this->_fetchedAllCalendars)
			{
				$this->_allCalendarIds = array_keys($this->_calendarsById);
			}
			else
			{
				$this->_allCalendarIds = craft()->db->createCommand()
					->select('id')
					->from('events_calendars')
					->queryColumn();
			}
		}

		return $this->_allCalendarIds;
	}

	/**
	 * Returns all calendars.
	 *
	 * @param string|null $indexBy
	 * @return array
	 */
	public function getAllCalendars($indexBy = null)
	{
		if (!$this->_fetchedAllCalendars)
		{
			$calendarRecords = Events_CalendarRecord::model()->ordered()->findAll();
			$this->_calendarsById = Events_CalendarModel::populateModels($calendarRecords, 'id');
			$this->_fetchedAllCalendars = true;
		}

		if ($indexBy == 'id')
		{
			return $this->_calendarsById;
		}
		else if (!$indexBy)
		{
			return array_values($this->_calendarsById);
		}
		else
		{
			$calendars = array();

			foreach ($this->_calendarsById as $calendar)
			{
				$calendars[$calendar->$indexBy] = $calendar;
			}

			return $calendars;
		}
	}

	/**
	 * Gets the total number of calendars.
	 *
	 * @return int
	 */
	public function getTotalCalendars()
	{
		return count($this->getAllCalendarIds());
	}

	/**
	 * Returns a calendar by its ID.
	 *
	 * @param $calendarId
	 * @return Events_CalendarModel|null
	 */
	public function getCalendarById($calendarId)
	{
		if (!isset($this->_calendarsById) || !array_key_exists($calendarId, $this->_calendarsById))
		{
			$calendarRecord = Events_CalendarRecord::model()->findById($calendarId);

			if ($calendarRecord)
			{
				$this->_calendarsById[$calendarId] = Events_CalendarModel::populateModel($calendarRecord);
			}
			else
			{
				$this->_calendarsById[$calendarId] = null;
			}
		}

		return $this->_calendarsById[$calendarId];
	}

	/**
	 * Gets a calendar by its handle.
	 *
	 * @param string $calendarHandle
	 * @return Events_CalendarModel|null
	 */
	public function getCalendarByHandle($calendarHandle)
	{
		$calendarRecord = Events_CalendarRecord::model()->findByAttributes(array(
			'handle' => $calendarHandle
		));

		if ($calendarRecord)
		{
			return Events_CalendarModel::populateModel($calendarRecord);
		}
	}

	/**
	 * Saves a calendar.
	 *
	 * @param Events_CalendarModel $calendar
	 * @throws \Exception
	 * @return bool
	 */
	public function saveCalendar(Events_CalendarModel $calendar)
	{
		if ($calendar->id)
		{
			$calendarRecord = Events_CalendarRecord::model()->findById($calendar->id);

			if (!$calendarRecord)
			{
				throw new Exception(Craft::t('No calendar exists with the ID “{id}”', array('id' => $calendar->id)));
			}

			$oldCalendar = Events_CalendarModel::populateModel($calendarRecord);
			$isNewCalendar = false;
		}
		else
		{
			$calendarRecord = new Events_CalendarRecord();
			$isNewCalendar = true;
		}

		$calendarRecord->name       = $calendar->name;
		$calendarRecord->handle     = $calendar->handle;

		$calendarRecord->validate();
		$calendar->addErrors($calendarRecord->getErrors());

		if (!$calendar->hasErrors())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
			try
			{
				if (!$isNewCalendar && $oldCalendar->fieldLayoutId)
				{
					// Drop the old field layout
					craft()->fields->deleteLayoutById($oldCalendar->fieldLayoutId);
				}

				// Save the new one
				$fieldLayout = $calendar->getFieldLayout();
				craft()->fields->saveLayout($fieldLayout);

				// Update the calendar record/model with the new layout ID
				$calendar->fieldLayoutId = $fieldLayout->id;
				$calendarRecord->fieldLayoutId = $fieldLayout->id;

				// Save it!
				$calendarRecord->save(false);

				// Now that we have a calendar ID, save it on the model
				if (!$calendar->id)
				{
					$calendar->id = $calendarRecord->id;
				}

				// Might as well update our cache of the calendar while we have it.
				$this->_calendarsById[$calendar->id] = $calendar;

				if ($transaction !== null)
				{
					$transaction->commit();
				}
			}
			catch (\Exception $e)
			{
				if ($transaction !== null)
				{
					$transaction->rollback();
				}

				throw $e;
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Deletes a calendar by its ID.
	 *
	 * @param int $calendarId
	 * @throws \Exception
	 * @return bool
	 */
	public function deleteCalendarById($calendarId)
	{
		if (!$calendarId)
		{
			return false;
		}

		$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
		try
		{
			// Delete the field layout
			$fieldLayoutId = craft()->db->createCommand()
				->select('fieldLayoutId')
				->from('events_calendars')
				->where(array('id' => $calendarId))
				->queryScalar();

			if ($fieldLayoutId)
			{
				craft()->fields->deleteLayoutById($fieldLayoutId);
			}

			// Grab the event ids so we can clean the elements table.
			$eventIds = craft()->db->createCommand()
				->select('id')
				->from('events')
				->where(array('calendarId' => $calendarId))
				->queryColumn();

			craft()->elements->deleteElementById($eventIds);

			$affectedRows = craft()->db->createCommand()->delete('events_calendars', array('id' => $calendarId));

			if ($transaction !== null)
			{
				$transaction->commit();
			}

			return (bool) $affectedRows;
		}
		catch (\Exception $e)
		{
			if ($transaction !== null)
			{
				$transaction->rollback();
			}

			throw $e;
		}
	}
}
