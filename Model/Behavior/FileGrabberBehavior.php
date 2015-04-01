<?php
App::uses('UploadBehavior', 'Upload.Model/Behavior');
App::uses('HttpSocket', 'Network/Http');

class FileGrabberBehavior extends UploadBehavior {

/**
 * Download remote file into PHP's TMP dir
 */
	protected function _grab(Model $model, $field, $uri) {

		$socket = new HttpSocket(array(
			'ssl_verify_host' => false
		));
		$socket->get($uri);
		$headers = $socket->response['header'];
		$file_name = basename($socket->request['uri']['path']);
		$tmp_file = sys_get_temp_dir() . '/' . $file_name;

		if ($socket->response['status']['code'] != 200) {
			return false;
		}

		$model->data[$model->alias][$field] = array(
			'name' => $file_name,
			'type' => $headers['Content-Type'],
			'tmp_name' => $tmp_file,
			'error' => 1,
			'size' => $headers['Content-Length'],
		);

		$file = file_put_contents($tmp_file, $socket->response['body']);
		if (!$file) {
			return false;
		}

		$model->data[$model->alias][$field]['error'] = 0;
		return true;
	}

/**
 * Transform Model.field value like as PHP upload array (name, tmp_name)
 * for UploadBehavior plugin processing.
 */
	public function beforeValidate(Model $model, $options = array()) {
		foreach($this->settings[$model->alias] as $field => $option) {
			$uri = $model->data[$model->alias][$field];
			if (empty($model->data[$model->alias][$field])) {
				continue;
			}
			if (!$this->_grab($model, $field, $uri)) {
				$model->invalidate($field, __d('upload', 'File was not downloaded.', true));
				return false;
			}
		}
		return true;
	}

	public function handleUploadedFile($modelAlias, $field, $tmp, $filePath) {
		return rename($tmp, $filePath) && $this->_chmod($filePath, $this->settings[$modelAlias][$field]['mask']);
	}

}
