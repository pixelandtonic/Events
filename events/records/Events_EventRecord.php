<?php
namespace Craft;

/**
 * Events - Event record
 */
class Events_EventRecord extends BaseRecord
{
	/**
	 * @return string
	 */
	public function getTableName()
	{
		return 'events';
	}

	/**
	 * @access protected
	 * @return array
	 */
	protected function defineAttributes()
	{
		return array(
			'startDate' => array(AttributeType::DateTime, 'required' => true),
			'endDate'   => array(AttributeType::DateTime, 'required' => true),
		);
	}

	/**
	 * @return array
	 */
	public function defineRelations()
	{
		return array(
			'element'  => array(static::BELONGS_TO, 'ElementRecord', 'id', 'required' => true, 'onDelete' => static::CASCADE),
			'calendar' => array(static::BELONGS_TO, 'Events_CalendarRecord', 'required' => true, 'onDelete' => static::CASCADE),
		);
	}
}
