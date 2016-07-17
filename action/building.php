<?php
// Получаем здания
class action_building
{

    // вывод зданий
    // http://api.what.tk/building
    public static function get_all()
    {
        $data = db::sql(
            "Select c.name city, s.name street, b.house, concat(c.name,', ',s.name,' ',b.house) address
            From building b
            JOIN city c on c.id=b.city_id
            JOIN street s on s.id=b.street_id
            Order by 1,2,3"
        );

        return app::decode($data);
    }

}