<?php
namespace Craft;

/**
 * Events controller
 */
class EventsController extends BaseController
{
	/**
	 * Event index
	 */
	public function actionEventIndex()
	{
		$variables['calendars'] = craft()->events_calendars->getAllCalendars();

		$this->renderTemplate('events/_index', $variables);
	}

	/**
	 * Edit an event.
	 *
	 * @param array $variables
	 * @throws HttpException
	 */
	public function actionEditEvent(array $variables = array())
	{
		if (!empty($variables['calendarHandle']))
		{
			$variables['calendar'] = craft()->events_calendars->getCalendarByHandle($variables['calendarHandle']);
		}
		else if (!empty($variables['calendarId']))
		{
			$variables['calendar'] = craft()->events_calendars->getCalendarById($variables['calendarId']);
		}

		if (empty($variables['calendar']))
		{
			throw new HttpException(404);
		}

		// Now let's set up the actual event
		if (empty($variables['event']))
		{
			if (!empty($variables['eventId']))
			{
				$variables['event'] = craft()->events->getEventById($variables['eventId']);

				if (!$variables['event'])
				{
					throw new HttpException(404);
				}
			}
			else
			{
				$variables['event'] = new Events_EventModel();
				$variables['event']->calendarId = $variables['calendar']->id;
			}
		}

		// Tabs
		$variables['tabs'] = array();

		foreach ($variables['calendar']->getFieldLayout()->getTabs() as $index => $tab)
		{
			// Do any of the fields on this tab have errors?
			$hasErrors = false;

			if ($variables['event']->hasErrors())
			{
				foreach ($tab->getFields() as $field)
				{
					if ($variables['event']->getErrors($field->getField()->handle))
					{
						$hasErrors = true;
						break;
					}
				}
			}

			$variables['tabs'][] = array(
				'label' => $tab->name,
				'url'   => '#tab'.($index+1),
				'class' => ($hasErrors ? 'error' : null)
			);
		}

		if (!$variables['event']->id)
		{
			$variables['title'] = Craft::t('Create a new event');
		}
		else
		{
			$variables['title'] = $variables['event']->title;
		}

		// Breadcrumbs
		$variables['crumbs'] = array(
			array('label' => Craft::t('Events'), 'url' => UrlHelper::getUrl('events')),
			array('label' => $variables['calendar']->name, 'url' => UrlHelper::getUrl('events'))
		);

		// Set the "Continue Editing" URL
		$variables['continueEditingUrl'] = 'events/'.$variables['calendar']->handle.'/{id}';

		// Render the template!
		$this->renderTemplate('events/_edit', $variables);
	}

	/**
	 * Saves an event.
	 */
	public function actionSaveEvent()
	{
		$this->requirePostRequest();

		$eventId = craft()->request->getPost('eventId');

		if ($eventId)
		{
			$event = craft()->events->getEventById($eventId);

			if (!$event)
			{
				throw new Exception(Craft::t('No event exists with the ID “{id}”', array('id' => $eventId)));
			}
		}
		else
		{
			$event = new Events_EventModel();
		}

		// Set the event attributes, defaulting to the existing values for whatever is missing from the post data
		$event->calendarId = craft()->request->getPost('calendarId', $event->calendarId);
		$event->startDate  = (($startDate = craft()->request->getPost('startDate')) ? DateTime::createFromString($startDate, craft()->timezone) : null);
		$event->endDate    = (($endDate   = craft()->request->getPost('endDate'))   ? DateTime::createFromString($endDate,   craft()->timezone) : null);

		$event->getContent()->title = craft()->request->getPost('title', $event->title);
		$event->setContentFromPost('fields');

		if (craft()->events->saveEvent($event))
		{
			craft()->userSession->setNotice(Craft::t('Event saved.'));
			$this->redirectToPostedUrl($event);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save event.'));

			// Send the event back to the template
			craft()->urlManager->setRouteVariables(array(
				'event' => $event
			));
		}
	}

	/**
	 * Deletes an event.
	 */
	public function actionDeleteEvent()
	{
		$this->requirePostRequest();

		$eventId = craft()->request->getRequiredPost('eventId');

		if (craft()->elements->deleteElementById($eventId))
		{
			craft()->userSession->setNotice(Craft::t('Event deleted.'));
			$this->redirectToPostedUrl();
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t delete event.'));
		}
	}
}
