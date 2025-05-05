<?php

namespace data;

class HotelFlag
{

    private static $hotel_flag = false;

    public static function isHotelFound()
    {
        return self::$hotel_flag;
    }

    public static function setHotelFlag()
    {
        if (self::$hotel_flag)
            self::$hotel_flag = false;
        else
            self::$hotel_flag = true;
    }

}

?>