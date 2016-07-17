<?php
// Получаем организации
class action_org
{
    // телефоны организации
    public static function get_phones($id = 0)
    {
        if(!$id) return;
        return $data = db::sql('[cache:900] Select oc.phone
            From org_contact oc
            Where oc.org_id = ?i',$id);
    }

    // рубрики организаций
    public static function get_rubrics($id = 0)
    {
        if(!$id) return;
        return $data = db::sql('[cache:900] Select r.name
        From org_rubric ro
        JOIN rubric r on r.id=ro.rubric_id
        Where ro.org_id = ?i',$id);
    }

    // полный адрес организации
    public static function get_address($id = 0)
    {
        if(!$id) return;
        return $data = db::one("[cache:900] Select c.name city, s.name street, b.house, concat(c.name,', ',s.name,' ',b.house) address, b.lat, b.lon
            From org o
            JOIN org_building ob on o.id=ob.org_id
            JOIN building b on ob.building_id=b.id
            JOIN city c on c.id=b.city_id
            JOIN street s on s.id=b.street_id
            Where ob.org_id = ?i",$id);
    }

    // вывод по id организации
    // http://api.what.tk/org/id/512
    public static function get_id($id = 0)
    {
        if(!$id) return app::error('Organizations id need');
        $data = db::one('[cache:900] Select name
            From org
            Where id = ?',$id);
        $data['phones'] = self::get_phones($id);
        $data['address'] = self::get_address($id);
        $data['rubric'] = self::get_rubrics($id);

        return app::decode($data);
    }

    // поиск по названию организации
    // http://api.what.tk/org?q=ТелеБухТяжЛизинг
    public static function get_name()
    {
        $_GET['q'] = trim($_GET['q']);
        if(!$_GET['q']) return app::error('Organizations name need');
        $data = db::one('[cache:900] Select id,name
            From org
            Where name LIKE ?','%'.$_GET['q'].'%');
        $data['phones'] = self::get_phones($data['id']);
        $data['address'] = self::get_address($data['id']);
        $data['rubric'] = self::get_rubrics($data['id']);

        return app::decode($data);
    }

    // вывод по id здания
    // http://api.what.tk/org/building/id/751
    public static function get_building_id($id = 0)
    {
        if(!$id)  return app::error('Buildings id need');
        $data = db::sql('[cache:900] Select name
            From org o JOIN org_building b on o.id=b.org_id
            Where b.building_id = ?',$id);

        return app::decode($data);
    }

    // организации по адресу
    // http://api.what.tk/org/building?q=Новосибирск, Ленина 14
    public static function get_building()
    {
        $_GET['city'] = trim($_GET['city']);
        $_GET['street'] = trim($_GET['street']);
        $_GET['house'] = trim($_GET['house']);
        if(!$_GET['city'] && !$_GET['street'] && !$_GET['house']) return app::error('Parameter "city","street" or "house" need');

        $data = db::sql("[cache:900] Select o.name
            From org o
            JOIN org_building ob on o.id=ob.org_id
            JOIN building b on ob.building_id=b.id
            JOIN city c on c.id=b.city_id
            JOIN street s on s.id=b.street_id
            Where true ".
            ($_GET['city'] ? "AND c.name = ? " : "/* ? */ ").
            ($_GET['street'] ? "AND s.name = ? " : "/* ? */ ").
            ($_GET['house'] ? "AND b.house = ? " : "/* ? */ "),
            $_GET['city'],
            $_GET['street'],
            $_GET['house']);

        return app::decode($data);
    }

    // поиск по id рубрики
    // http://api.what.tk/org/rubric/id/16
    public static function get_rubric_id($id = 0)
    {
        if(!$id) return app::error('Rubrics id need');
        $data = db::sql('[cache:900] Select o.name
        From org o JOIN org_rubric ro on o.id=ro.org_id
        Where ro.rubric_id = ?i',$id);

        return app::decode($data);
    }

    // поиск по рубрике
    // http://api.what.tk/org/rubric?q=сантехника
    public static function get_rubric()
    {
        $_GET['q'] = trim($_GET['q']);
        if(!$_GET['q']) return app::error('Rubrics name need');
        $data = db::sql('[cache:900] Select o.name
        From org o JOIN org_rubric ro on o.id=ro.org_id
        JOIN rubric r on r.id=ro.rubric_id
        Where r.name LIKE ?','%'.$_GET['q'].'%');

        return app::decode($data);
    }

    // поиск по координатам и радиусу
    // пример http://api.what.tk/org/coord?lat=55.0833&lon=82.642&radius=1000
    public static function get_coord()
    {
        if(!$_GET['lat'] && !$_GET['lat']) return app::error('Parameter lat and lon need');
        if(!$_GET['radius']) $_GET['radius'] = 1000;

        $data = db::sql('[cache:900] Select o.name
            From org o JOIN org_building ob on o.id=ob.org_id
            JOIN building b on ob.building_id=b.id
            Where 6371 * 1000 * 2 * ASIN(SQRT(
            POWER(SIN ((b.lat - ABS(?f)) * PI()/ 180 / 2 ), 2) +
            COS(b.lat * PI()/180) *
            COS(ABS (?f) * PI()/180) *
            POWER(SIN ((b.lon - ?f) * PI()/180 / 2), 2 )
            )) <= ?i', $_GET['lat'], $_GET['lat'], $_GET['lon'],$_GET['radius']);

        return app::decode($data);
    }

}