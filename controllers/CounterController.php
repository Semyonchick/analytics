<?php

/**
 * Class CounterController
 * Контроллер, на который приходит запрос с сайта от JS скрипта
 */
class CounterController extends RController
{

	public function actionIndex($id, $name = null)
	{
		if(!Yii::app()->request->isAjaxRequest)
			throw new CHttpException(404);
		$this->response(array('success' => true));
		$data = Yii::app()->user->getState(md5('analytics:' . $id));
		Yii::app()->user->setState(md5('analytics:' . $id), false);
		AnalyticsHelper::incrementLog(array(
			'url' => $data['url'],
			'name' => $name,
			'referrer' => $data['referrer'],
			'userAgent' => Yii::app()->request->userAgent,
			'ip' => Yii::app()->request->userHostAddress,
			'session' => Yii::app()->session->getSessionID(),
			'time_cpu' => $data['cpu'] * 1000,
			'time_exec' => $data['time'] * 1000,
			'ram' => $data['ram'] * 1000,
			'created' => time(),
		));
	}

	public function response($data)
	{
		ob_start();
		echo json_encode($data);
		$size = ob_get_length();
		header("Content-Length: $size");
		header('Connection: close');
		ob_end_flush();
		ob_flush();
		if (session_id())
			session_write_close();
	}

}
