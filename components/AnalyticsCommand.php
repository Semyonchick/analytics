<?php

YiiBase::setPathOfAlias('analytics', dirname(dirname(__FILE__)));
YiiBase::import('analytics.components.AnalyticsHelper');

/**
 * Class AnalyticsCommand
 * Консольная команда для аналитики
 */
class AnalyticsCommand extends RConsoleCommand
{

    public function actionIndex($delete = true, $deleteAll = false)
    {
        $startTime = time();

        $db = Yii::app()->db;
        if ($deleteAll) $db->createCommand()->delete('log_visit');
        $totalCount = $db->createCommand()->from('statistic')->select('COUNT(*)')->queryScalar();

        $this->e("Convert data...");
        $i = 0;
        Yii::app()->db->autoCommit = false;
        while ($part = $db->createCommand()->from('statistic')->order('lastmod')->limit(10000, $delete ? 0 : $i)->queryAll()):
            foreach ($part as $row):
                AnalyticsHelper::incrementLog(array(
                    'url' => $row['url'],
                    'referrer' => $row['url_referrer'],
                    'userAgent' => $row['user_agent'],
                    'ip' => $row['ip'],
                    'session' => $row['url_referrer'] . $row['user_agent'],
                    'time_cpu' => $row['cpu'] * 1000,
                    'time_exec' => $row['time'] * 1000,
                    'ram' => $row['memory'] * 1000,
                    'created' => strtotime($row['lastmod']),
                ));
                if ($delete) $db->createCommand()->delete('statistic', 'id=:id', array('id' => $row['id']));

                $i++;
                $this->cursorUp();
                $this->e("{$i}/{$totalCount}        ");
            endforeach;
            Yii::app()->db->autoCommit = true;
            $this->clear();
        endwhile;

        if ($time = time() - $startTime) {
            $speed = round($totalCount / $time, 2);
            $this->e("Fetching data is done, overall speed: {$speed} rows/sec");
            $this->e("Done in {$time} seconds");
        } else {
            $this->e("Fetching data is done, overall speed: INFINITY");
        }
    }

    public function actionDecodeIp($id)
    {
        $ipData = Yii::app()->db->createCommand()->select('location_ip')->from('log_visit')->where("id = :id")->queryScalar(array(':id' => $id));
        $this->e(AnalyticsHelper::n2p($ipData));
    }

}
