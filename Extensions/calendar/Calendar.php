<?php
/**
 * Created by PhpStorm.
 * User: Sandro
 * Date: 27.11.14
 * Time: 19:14
 */

class Calendar
{
    const TABLE_EVENT = "event";
    const  TABLE_EVENTTEILNAHME = "eventteilnahme";

    function __construct($view, $type = null)
    {
        $this->db = $view->model->db;

        if($type == null)
            $this->setType(CalendarType::USER_CALENDAR);
        else
            $this->setType($type);
    }

    function show($id)
    {
        return "<div id='calendar'></div>";
    }

    function setType($type)
    {
        $this->type = $type;
    }
}

class CalendarType
{
    //Every user has a different Calendar -> based on user id
    const USER_CALENDAR = 0;
    //same calendars for all users -> based on calendar id
    const PUBLC_CALENDAR = 1;
}
?> 