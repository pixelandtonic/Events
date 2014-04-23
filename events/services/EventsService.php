<?php
namespace Craft;

/**
 * Events service
 */
class EventsService extends BaseApplicationComponent
{
	/**
	 * Returns an event by its ID.
	 *
	 * @param int $eventId
	 * @return Events_EventModel|null
	 */
	public function getEventById($eventId)
	{
		return craft()->elements->getElementById($eventId, 'Events_Event');
	}

	/**
	 * Saves an event.
	 *
	 * @param Events_EventModel $event
	 * @throws Exception
	 * @return bool
	 */
	public function saveEvent(Events_EventModel $event)
	{
		$isNewEvent = !$event->id;

		// Event data
		if (!$isNewEvent)
		{
			$eventRecord = Events_EventRecord::model()->findById($event->id);

			if (!$eventRecord)
			{
				throw new Exception(Craft::t('No event exists with the ID “{id}”', array('id' => $event->id)));
			}
		}
		else
		{
			$eventRecord = new Events_EventRecord();
		}

		$eventRecord->calendarId = $event->calendarId;
		$eventRecord->startDate  = $event->startDate;
		$eventRecord->endDate    = $event->endDate;

		$eventRecord->validate();
		$event->addErrors($eventRecord->getErrors());

		if (!$event->hasErrors())
		{
			$transaction = craft()->db->getCurrentTransaction() === null ? craft()->db->beginTransaction() : null;
			try
			{
				// Fire an 'onBeforeSaveEvent' event
				$this->onBeforeSaveEvent(new Event($this, array(
					'event'      => $event,
					'isNewEvent' => $isNewEvent
				)));

				if (craft()->elements->saveElement($event))
				{
					// Now that we have an element ID, save it on the other stuff
					if ($isNewEvent)
					{
						$eventRecord->id = $event->id;
					}

					$eventRecord->save(false);

					// Fire an 'onSaveEvent' event
					$this->onSaveEvent(new Event($this, array(
						'event'      => $event,
						'isNewEvent' => $isNewEvent
					)));

					if ($transaction !== null)
					{
						$transaction->commit();
					}

					return true;
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
		}

		return false;
	}

	// Events

	/**
	 * Fires an 'onBeforeSaveEvent' event.
	 *
	 * @param Event $event
	 */
	public function onBeforeSaveEvent(Event $event)
	{
		$this->raiseEvent('onBeforeSaveEvent', $event);
	}

	/**
	 * Fires an 'onSaveEvent' event.
	 *
	 * @param Event $event
	 */
	public function onSaveEvent(Event $event)
	{
		$this->raiseEvent('onSaveEvent', $event);
	}
}
