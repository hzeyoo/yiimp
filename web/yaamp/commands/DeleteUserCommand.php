<?php
/**
 * DeleteUserCommand is a console command, to delete an user and its history
 *
 * To use this command, enter the following on the command line:
 * <pre>
 * yiic deleteuser <id|addr>
 * </pre>
 *
 * @property string $help The command description.
 *
 */
class DeleteUserCommand extends CConsoleCommand
{
	protected $basePath;

	/**
	 * Execute the action.
	 * @param array $args command line parameters specific for this command
	 * @return integer non zero application exit code after printing help
	 */
	public function run($args)
	{
		$runner=$this->getCommandRunner();
		$commands=$runner->commands;

		$root = realpath(Yii::app()->getBasePath().DIRECTORY_SEPARATOR.'..');
		$this->basePath = str_replace(DIRECTORY_SEPARATOR, '/', $root);

		if (!isset($args[0]) || $args[0] == 'help') {

			echo "Yii deleteuser command\n";
			echo "Usage: yiic deleteuser <id|address>\n";
			return 1;

		} else {

			$id = -1; $addr = '';
			if (strlen($args[0]) < 26)
				$id = (int) $args[0];
			else
				$addr = $args[0];
			$this->deleteUser($id, $addr);
			return 0;
		}
	}

	/**
	 * Provides the command description.
	 * @return string the command description.
	 */
	public function getHelp()
	{
		return $this->run(array('help'));
	}

	/**
	 * Delete user by id or wallet address
	 */
	public function deleteUser($id, $addr)
	{
		$nbDeleted = 0;

		$users = new db_accounts;
		$user = $users->find(array('condition'=>'id=:id OR username=:username', 'params'=>array(
			':id'=>$id, ':username'=>$addr,
		)));
		if ($user && $user->id)	{
			$nbDeleted += $user->deleteWithDeps();
		} else {
			echo "user not found!\n";
		}
		echo "$nbDeleted user deleted\n";
	}
}
