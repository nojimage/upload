<?php
App::uses('UploadBehavior', 'Upload.Model/Behavior');
App::uses('File', 'Utility');

class FileImportBehavior extends UploadBehavior {

	protected function _grab(Model $model, $field, $filePath) {
		if (!is_file($filePath)) {
			return false;
		}

		$file = new File($filePath);
		$info = $file->info();

		$model->data[$model->alias][$field] = array(
			'name' => $info['basename'],
			'type' => $info['mime'],
			'tmp_name' => $filePath,
			'error' => 0,
			'size' => $info['filesize'],
		);

		return true;
	}

	public function beforeValidate(Model $model) {
		foreach ($this->settings[$model->alias] as $field => $options) {
			$filepath = $model->data[$model->alias][$field];
			if (empty($model->data[$model->alias][$field])) {
				continue;
			}
			if (!$this->_grab($model, $field, $filepath)) {
				$model->invalidate($field, __d('upload', 'File not found.', true));
				return false;
			}
		}
		return true;
	}

	public function handleUploadedFile($modelAlias, $field, $tmp, $filePath) {
		return copy($tmp, $filePath) && $this->_chmod($filePath, $this->settings[$modelAlias][$field]['mask']);
	}

}