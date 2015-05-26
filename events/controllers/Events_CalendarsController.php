<?php
namespace Craft;

/**
 * Calendars controller
 */
class Events_CalendarsController extends BaseController
{
	/**
	 * Calendar index
	 */
	public function actionCalendarIndex()
	{
		$variables['calendars'] = craft()->events_calendars->getAllCalendars();

		$this->renderTemplate('events/calendars', $variables);
	}

	/**
	 * Edit a calendar.
	 *
	 * @param array $variables
	 * @throws HttpException
	 * @throws Exception
	 */
	public function actionEditCalendar(array $variables = array())
	{
		$variables['brandNewCalendar'] = false;

		if (!empty($variables['calendarId']))
		{
			if (empty($variables['calendar']))
			{
				$variables['calendar'] = craft()->events_calendars->getCalendarById($variables['calendarId']);

				if (!$variables['calendar'])
				{
					throw new HttpException(404);
				}
			}

			$variables['title'] = $variables['calendar']->name;
		}
		else
		{
			if (empty($variables['calendar']))
			{
				$variables['calendar'] = new Events_CalendarModel();
				$variables['brandNewCalendar'] = true;
			}

			$variables['title'] = Craft::t('Create a new calendar');
		}

		$variables['crumbs'] = array(
			array('label' => Craft::t('Events'), 'url' => UrlHelper::getUrl('events')),
			array('label' => Craft::t('Calendars'), 'url' => UrlHelper::getUrl('events/calendars')),
		);

		$this->renderTemplate('events/calendars/_edit', $variables);
	}

	/**
	 * Saves a calendar
	 */
	public function actionSaveCalendar()
	{
		$this->requirePostRequest();

		$calendar = new Events_CalendarModel();

		// Shared attributes
		$calendar->id         = craft()->request->getPost('calendarId');
		$calendar->name       = craft()->request->getPost('name');
		$calendar->handle     = craft()->request->getPost('handle');

		// Set the field layout
		$fieldLayout = craft()->fields->assembleLayoutFromPost();
		$fieldLayout->type = 'Events_Event';
		$calendar->setFieldLayout($fieldLayout);

		// Save it
		if (craft()->events_calendars->saveCalendar($calendar))
		{
			craft()->userSession->setNotice(Craft::t('Calendar saved.'));
			$this->redirectToPostedUrl($calendar);
		}
		else
		{
			craft()->userSession->setError(Craft::t('Couldn’t save calendar.'));
		}

		// Send the calendar back to the template
		craft()->urlManager->setRouteVariables(array(
			'calendar' => $calendar
		));
	}

	/**
	 * Deletes a calendar.
	 */
	public function actionDeleteCalendar()
	{
		$this->requirePostRequest();
		$this->requireAjaxRequest();

		$calendarId = craft()->request->getRequiredPost('id');

		craft()->events_calendars->deleteCalendarById($calendarId);
		$this->returnJson(array('success' => true));
	}
}
