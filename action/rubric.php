<?php
// Получаем здания
class action_rubric
{
    function build_tree($cats,$parent_id)
    {
        if (is_array($cats) && $cats[$parent_id]) {
                foreach ($cats[$parent_id] as $cat) {
                    $tree[$cat['name']] = self::build_tree($cats, $cat['id']);
                }
            };
        return $tree;
        }

    // вывод дерева рубрик
    // http://api.what.tk/rubric
    public static function get_all()
    {
        $rubric = db::sql(
            'Select *
            From rubric
            Order by id,parent_id'
        );

        foreach($rubric as $r){
            $res[$r['parent_id']][$r['id']] =  $r;
        }

        $data = self::build_tree($res,0);

        return app::decode($data);
    }

}