<?php
namespace Employees\Common\Model;

use Classes\BaseService;
use Classes\FileService;
use Classes\IceResponse;
use Classes\ModuleAccess;
use Classes\PermissionManager;
use Company\Common\Model\CompanyStructure;
use Metadata\Common\Model\Country;
use Model\BaseModel;
use Model\CustomFieldTrait;
use Model\File;
use Utils\CalendarTools;

class Employee extends BaseModel
{

    public $oldObj = null;
    public $oldObjOrig = null;
    public $historyUpdateList = array();
    public $historyFieldsToTrack = array(
        "employee_id"=>"employee_id",
        "first_name"=>"first_name",
        "middle_name"=>"middle_name",
        "last_name"=>"last_name",
        "nationality"=>"nationality_Name",
        "birthday"=>"birthday",
        "gender"=>"gender",
        "marital_status"=>"marital_status",
        "ssn_num"=>"ssn_num",
        "nic_num"=>"nic_num",
        "other_id"=>"other_id",
        "employment_status"=>"employment_status_Name",
        "job_title"=>"job_title_Name",
        "pay_grade"=>"pay_grade_Name",
        "work_station_id"=>"work_station_id",
        "address1"=>"address1",
        "address2"=>"address2",
        "city"=>"city_Name",
        "country"=>"country_Name",
        "province"=>"province_Name",
        "postal_code"=>"postal_code",
        "home_phone"=>"home_phone",
        "mobile_phone"=>"mobile_phone",
        "work_phone"=>"work_phone",
        "work_email"=>"work_email",
        "private_email"=>"private_email",
        "joined_date"=>"joined_date",
        "confirmation_date"=>"confirmation_date",
        "supervisor"=>"supervisor_Name",
        "indirect_supervisors"=>"indirect_supervisors",
        "department"=>"department_Name"
    );

    public function getAdminAccess()
    {
        return array("get","element","save","delete");
    }

    public function getManagerAccess()
    {
        return array("get","element","save");
    }

    public function getUserAccess()
    {
        return array("get");
    }

    public function getUserOnlyMeAccess()
    {
        return array("element","save");
    }

    public function getUserOnlyMeAccessField()
    {
        return "id";
    }

    private function initHistory($obj)
    {

        $oldObjOrig = new Employee();
        $oldObjOrig->Load("id = ?", array($obj->id));
        $this->oldObjOrig = $oldObjOrig;

        $mapping = '{"nationality":["Nationality","id","name"],'
            .'"employment_status":["EmploymentStatus","id","name"],"job_title":["JobTitle","id","name"],'
            .'"pay_grade":["PayGrade","id","name"],"country":["Country","code","name"],'
            .'"province":["Province","id","name"],"department":["CompanyStructure","id","title"],'
            .'"supervisor":["Employee","id","first_name+last_name"]}';

        $this->oldObj = BaseService::getInstance()->getElement('Employee', $obj->id, $mapping, true);
    }

    public function isCustomFieldsEnabled()
    {
        return true;
    }

    private function saveHistory($obj)
    {

        $oldObj = $this->oldObj;
        $oldObjOrig = $this->oldObjOrig;

        $mapping = '{"nationality":["Nationality","id","name"],'
            .'"employment_status":["EmploymentStatus","id","name"],"job_title":["JobTitle","id","name"],'
            .'"pay_grade":["PayGrade","id","name"],"country":["Country","code","name"],'
            .'"province":["Province","id","name"],"department":["CompanyStructure","id","title"],'
            .'"supervisor":["Employee","id","first_name+last_name"]}';

        $objEnriched = BaseService::getInstance()->getElement('Employee', $obj->id, $mapping, true);

        foreach ($this->historyFieldsToTrack as $k => $v) {
            if (empty($oldObjOrig->$k) && $obj->$k = '[]') {
                continue;
            }

            if (empty($obj->$k) && $oldObjOrig->$k == '0000-00-00') {
                continue;
            }

            if ($oldObjOrig->$k != $obj->$k) {
                $enrichNewVal = '';
                $enrichOldVal = '';

                if ($k == 'indirect_supervisors') {
                    if (!empty($obj->$k) && $obj->$k != '[]') {
                        $newIndeirectSupervisorIds = json_decode($obj->$k);
                        foreach ($newIndeirectSupervisorIds as $id) {
                            $item = BaseService::getInstance()->getItemFromCache("Employee", $id);
                            if ($enrichNewVal != "") {
                                $enrichNewVal .= ", ";
                            }
                            $enrichNewVal .= $item->first_name." ".$item->last_name;
                        }
                    }

                    if (!empty($oldObjOrig->$k) && $oldObjOrig->$k != '[]') {
                        $oldIndeirectSupervisorIds = json_decode($oldObjOrig->$k);
                        foreach ($oldIndeirectSupervisorIds as $id) {
                            $item = BaseService::getInstance()->getItemFromCache("Employee", $id);
                            if ($enrichOldVal != "") {
                                $enrichOldVal .= ", ";
                            }
                            $enrichOldVal .= $item->first_name." ".$item->last_name;
                        }
                    }
                } else {
                    $enrichOldVal = $oldObj->$v;
                    $enrichNewVal = $objEnriched->$v;
                }

                $this->historyUpdateList[] = array($obj->id,$k,$enrichOldVal,$enrichNewVal);
            }
        }

        while (count($this->historyUpdateList)) {
            $ele = array_pop($this->historyUpdateList);
            BaseService::getInstance()->addHistoryItem("Employee", "Employee", $ele[0], $ele[1], $ele[2], $ele[3]);
        }
    }

    public function executePreSaveActions($obj)
    {
        if (empty($obj->status)) {
            $obj->status = 'Active';
        }
        return new IceResponse(IceResponse::SUCCESS, $obj);
    }

    public function executePreUpdateActions($obj)
    {
		$savedObject = new Employee();
		$savedObject->Load('id = ?', [$obj->id]);
		$obj = $this->processBasicPermissions($obj, $savedObject);
        $this->initHistory($obj);
        return new IceResponse(IceResponse::SUCCESS, $obj);
    }

	protected function processBasicPermissions( $obj, $savedObject ) {
		$user = BaseService::getInstance()->getCurrentUser();
		$permissions = BaseService::getInstance()->loadModulePermissions('modules>employees', $user->user_level)['perm'];
		if ($permissions['Edit Employee Number'] === 'No' ) {
			$obj->employee_id = $savedObject->employee_id;
		}

		return $obj;
	}

    public function executePostUpdateActions($obj)
    {
        $this->saveHistory($obj);
    }

    public function postProcessGetData($obj)
    {
        if (empty($obj->timezone)) {
            $obj->timezone = BaseService::getInstance()->getEmployeeTimeZone($obj->id);
        }
        $obj = FileService::getInstance()->updateSmallProfileImage($obj);
        return $this->updateEmployeeUserEmail($obj);
    }

    public function getVirtualFields()
    {
        return array(
            "image"
        );
    }

    public function getActiveEmployees()
    {
        $employee = new Employee();
        $list = $employee->Find("status = ?", array('Active'));
        return $list;
    }

    public function getActiveSubordinateEmployees()
    {

        $employee = new Employee();

        if (BaseService::getInstance()->currentUser->user_level != 'Admin' && !EmployeeAccess::hasAccessToAllEmployeeData()) {
            $cemp = BaseService::getInstance()->getCurrentProfileId();
            $list = $employee->Find("status = ? and supervisor = ?", array('Active', $cemp));
        } else {
            $list = $employee->Find("status = ?", array('Active'));
        }
        return $list;
    }

    public static function cleanEmployeeData($obj)
    {
        unset($obj->keysToIgnore);
        unset($obj->historyFieldsToTrack);
        unset($obj->historyUpdateList);
        unset($obj->oldObjOrig);
        unset($obj->oldObj);
        return $obj;
    }

    public function fieldValueMethods()
    {
        return ['getActiveSubordinateEmployees'];
    }

    public static function getCurrentEmployeeCompanyStructureCountry()
    {
        $cemp = BaseService::getInstance()->getCurrentProfileId();
        $employee = new Employee();
        $employee->Load('id = ?', [$cemp]);

        $companyStructure = new CompanyStructure();
        $companyStructure->Load('id = ?', [$employee->department]);

        $country = new Country();
        $country->Load('code = ?', [$companyStructure->country]);

        return $country->id;
    }

    public function getModuleAccess()
    {
        return [
            new ModuleAccess('employees', 'admin'),
            new ModuleAccess('employees', 'user'),
        ];
    }

    public function postProcessGetElement($obj)
    {
        if (empty($obj->timezone)) {
            $obj->timezone = BaseService::getInstance()->getEmployeeTimeZone($obj->id);
        }
        $obj->current_time = CalendarTools::getServerDate('Y-m-d H:i:s', $obj->id);
        $obj = FileService::getInstance()->updateProfileImage($obj);
        return $this->updateEmployeeUserEmail($obj);
    }

    public $table = 'Employees';

    /**
     * @param $obj
     * @return mixed
     */
    protected function updateEmployeeUserEmail($obj)
    {
        $user = BaseService::getInstance()->getEmployeeUser($obj->id);
        if (null !== $user) {
            $obj->email = $user->email;
        } else {
            $obj->email = $obj->work_email;
        }
        return $obj;
    }
}
