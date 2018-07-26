<?php
/**
 * Description: The main class for the MyFamily app. It acts as a Controller.
 *
 * @author Nitin Patil
 */
class MyFamily {
    
    /** The logger. */
    private $logger;
    
    /** The delegate. */
    private $delegate;
    
    /** The UI. */
    private $ui;
    
    
    /**
     * Initialize the controller.
     */
    public function __construct() {
        // Load classes
        require_once '3rdparty/Facebook/autoload.php';
        require_once 'common/MyFamilyConstants.php';
        require_once 'core/MyFamilyDelegate.php';
        require_once 'ui/MyFamilyUI.php';
        require_once 'helper/FBHelper.php';
        require_once 'utils/Logger.php';
        
        $config = $this->readConfigFile();
        $this->logger = new Logger('myfamily.log');
        $this->logger->setLogLevel($config['logLevel']);
        $this->delegate = new MyFamilyDelegate($config, $this->logger);
        $this->ui = new MyFamilyUI($this->logger);
    }
    
    /**
     * Shows the menu.
     */
    public function showMenu() {
        // Reuse access token (if any)
        $accessToken = $this->delegate->getAccessToken();
        $this->delegate->setAccessToken($accessToken); // Reapply the token to make sure all layers have the latest token
        $this->ui->setAccessToken($this->delegate->getAccessToken()); // Ensure UI has the latest token
        $this->ui->showMenu();
    }
    
    /**
     * Processes the request.
     */
    public function process() {
        $response = [];
        $user = null;
        try {
            $data = isset($_POST) ? $_POST : [];
            $cmd = isset($data[MyFamilyConstants::$CMD_PARAM]) ? $data[MyFamilyConstants::$CMD_PARAM] : MyFamilyConstants::$SHOW_HOME_CMD;
            $this->logger->debug("MyFamily::process(): cmd: $cmd");
            if ($this->logger->isDebugEnabled()) {
                $this->logger->debug(sprintf("MyFamily::process(): data: %s", json_encode($data)));
            }
            
            $this->delegate->init();
            
            // Reuse access token (if any)
            $accessToken = $this->delegate->getAccessToken();
            $this->delegate->setAccessToken($accessToken); // Reapply the token to make sure all layers have the latest token
            
            $user = $this->delegate->getCurrentUser();
            if ($user) {
                $this->ui->setAccessToken($this->delegate->getAccessToken()); // Ensure UI has the latest token
                if ($cmd == MyFamilyConstants::$SHOW_HOME_CMD) {
                    $this->ui->showHome($user);
                }
                else if ($cmd == MyFamilyConstants::$SHOW_ADD_USER_FORM_CMD || $cmd == MyFamilyConstants::$SHOW_EDIT_USER_FORM_CMD) {
                    $genders = $this->delegate->getGenders();
                    $this->ui->showEditUserForm($cmd, $genders, $user);
                }
                else if ($cmd == MyFamilyConstants::$SHOW_MANAGE_FAMILIES_FORM_CMD) {
                    $families = $this->delegate->getUserManagedFamilies($user, true);
                    $familyID = $this->delegate->getUserDefaultFamilyID($user);
                    $this->ui->showManageFamiliesForm($user, $families, $familyID);
                }
                else if ($cmd == MyFamilyConstants::$SHOW_ADD_FAMILY_FORM_CMD || $cmd == MyFamilyConstants::$SHOW_EDIT_FAMILY_FORM_CMD) {
                    $familyID = $data[MyFamilyConstants::$FAMILY_ID_PARAM];
                    $family = null;
                    if ($cmd == MyFamilyConstants::$SHOW_EDIT_FAMILY_FORM_CMD) {
                        $family = $this->delegate->getFamily($familyID);
                    }
                    $this->ui->showEditFamilyForm($cmd, $user, $family);
                }
                else if ($cmd == MyFamilyConstants::$MANAGE_FAMILY_MEMBERS_CMD) {
                    $familyID = $data[MyFamilyConstants::$FAMILY_ID_PARAM];
                    $familyID = ($familyID) ? $familyID : $this->delegate->getUserDefaultFamilyID($user);
                    $families = $this->delegate->getUserFamilies($user);
                    $members = [];
                    $currentMember = null;
                    if ($familyID) {
                        $members = $this->delegate->getFamilyMembers($familyID);
                        $currentMember = $this->delegate->getFamilyMember($familyID, $user[MyFamilyConstants::$ID_PARAM]);
                    }
                    $this->ui->showFamilyMembers($families, $familyID, $members, $currentMember);
                }
                else if ($cmd == MyFamilyConstants::$SHOW_ADD_FAMILY_MEMBER_FORM_CMD || $cmd == MyFamilyConstants::$SHOW_EDIT_FAMILY_MEMBER_FORM_CMD) {
                    $familyID = $data[MyFamilyConstants::$FAMILY_ID_PARAM];
                    $familyID = ($familyID) ? $familyID : $this->delegate->getUserDefaultFamilyID($user);
                    $memberID = $data[MyFamilyConstants::$MEMBER_ID_PARAM];
                    $families = null;
                    $members = [];
                    $member = null;
                    $currentMember = $this->delegate->getFamilyMember($familyID, $user[MyFamilyConstants::$ID_PARAM]);
                    // Only a family manager should be allowed to add members, but a member can edit her details
                    if ($cmd == MyFamilyConstants::$SHOW_ADD_FAMILY_MEMBER_FORM_CMD) {
                        $families = $this->delegate->getUserManagedFamilies($user);
                    }
                    else {
                        $families = $this->delegate->getUserFamilies($user);
                    }
                    if ($this->delegate->isFamilyInList($families, $familyID)) {
                        $members = $this->delegate->getFamilyMembers($familyID);
                        if ($memberID) {
                            $member = $this->delegate->getFamilyMember($familyID, $memberID);
                        }
                    }
                    $relations = $this->delegate->getRelations();
                    $friends = null;
                    $managedUsers = null;
                    if ($cmd == MyFamilyConstants::$SHOW_ADD_FAMILY_MEMBER_FORM_CMD) {
                        $connections = $this->delegate->getCurrentUserFriendsAndManagedUsers($user);
                        $friends = $connections[MyFamilyConstants::$FRIENDS];
                        $managedUsers = $relations[MyFamilyConstants::$MANAGED_USERS];
                    }
                    $this->ui->showEditFamilyMemberForm($cmd, $families, $familyID, $members, $member, $currentMember, $relations, $friends, $managedUsers);
                }
                else if ($cmd == MyFamilyConstants::$GET_QUESTIONS_CONTROL_CMD) {
                    $questions = $this->delegate->getQuestions();
                    $this->ui->getQuestionsControl($questions);
                }
                else if ($cmd == MyFamilyConstants::$SHOW_MEMBER_RESPONSES_FORM_CMD) {
                    $memberID = $data[MyFamilyConstants::$MEMBER_ID_PARAM];
                    $familyID = $data[MyFamilyConstants::$FAMILY_ID_PARAM];
                    $questions = $this->delegate->getQuestions();
                    $responses = $this->delegate->getMemberResponses($memberID, $familyID);
                    $this->ui->showMemberResponsesForm($memberID, $familyID, $questions, $responses);
                }
                else if ($cmd == MyFamilyConstants::$SHOW_FAMILY_TREE_FORM_CMD) {
                    $familyID = $data[MyFamilyConstants::$FAMILY_ID_PARAM];
                    $familyID = ($familyID) ? $familyID : $this->delegate->getUserDefaultFamilyID($user);
                    $families = $this->delegate->getUserFamilies($user);
                    $this->ui->showFamilyTreeForm($families, $familyID);
                }
                else if ($cmd == MyFamilyConstants::$SHOW_MEMBER_DETAILS_CMD) {
                    $memberID = $data[MyFamilyConstants::$MEMBER_ID_PARAM];
                    $familyID = $data[MyFamilyConstants::$FAMILY_ID_PARAM];
                    $member = $this->delegate->getUser($memberID);
                    $questions = $this->delegate->getQuestions();
                    $responses = $this->delegate->getMemberResponses($memberID, $familyID);
                    $this->ui->showMemberDetails($familyID, $member, $questions, $responses);
                }
                else if ($cmd == MyFamilyConstants::$SHOW_SETTINGS_FORM_CMD) {
                    $settings = $this->delegate->getUserSettings($user);
                    $families = $this->delegate->getUserFamilies($user);
                    $this->ui->showUserSettingsForm($user, $settings, $families);
                }
                else if ($cmd == MyFamilyConstants::$EDIT_USER_CMD || $cmd == MyFamilyConstants::$ADD_USER_CMD) {
                    $status = $this->delegate->saveUser($cmd, $data);
                    if ($status) {
                        $data = [MyFamilyConstants::$MESSAGE => _('The user has been saved successfully.')];
                        $response[MyFamilyConstants::$CODE] = MyFamilyConstants::$SUCCESS_CODE;
                        $response[MyFamilyConstants::$DATA] = $data;
                    }
                    else {
                        $data = [MyFamilyConstants::$ERROR => _('The user could not be saved.')];
                        $response[MyFamilyConstants::$CODE] = MyFamilyConstants::$FAILURE_CODE;
                        $response[MyFamilyConstants::$DATA] = $data;
                    }
                }
                else if ($cmd == MyFamilyConstants::$EDIT_FAMILY_CMD || $cmd == MyFamilyConstants::$ADD_FAMILY_CMD) {
                    $status = $this->delegate->saveFamily($cmd, $user, $data);
                    if ($status) {
                        $data = [MyFamilyConstants::$MESSAGE => _('The family has been saved successfully.')];
                        $response[MyFamilyConstants::$CODE] = MyFamilyConstants::$SUCCESS_CODE;
                        $response[MyFamilyConstants::$DATA] = $data;
                    }
                    else {
                        $data = [MyFamilyConstants::$ERROR => _('The family could not be saved.')];
                        $response[MyFamilyConstants::$CODE] = MyFamilyConstants::$FAILURE_CODE;
                        $response[MyFamilyConstants::$DATA] = $data;
                    }
                }
                else if ($cmd == MyFamilyConstants::$DELETE_FAMILY_CMD) {
                    $status = $this->delegate->deleteFamily($data);
                    if ($status) {
                        $data = [MyFamilyConstants::$MESSAGE => _('The family has been deleted successfully.')];
                        $response[MyFamilyConstants::$CODE] = MyFamilyConstants::$SUCCESS_CODE;
                        $response[MyFamilyConstants::$DATA] = $data;
                    }
                    else {
                        $data = [MyFamilyConstants::$ERROR => _('The family could not be deleted.')];
                        $response[MyFamilyConstants::$CODE] = MyFamilyConstants::$FAILURE_CODE;
                        $response[MyFamilyConstants::$DATA] = $data;
                    }
                }
                else if ($cmd == MyFamilyConstants::$EDIT_FAMILY_MEMBER_CMD || $cmd == MyFamilyConstants::$ADD_FAMILY_MEMBER_CMD) {
                    $status = $this->delegate->saveFamilyMember($cmd, $data);
                    if ($status) {
                        $data = [MyFamilyConstants::$MESSAGE => _('The family member has been saved successfully.')];
                        $response[MyFamilyConstants::$CODE] = MyFamilyConstants::$SUCCESS_CODE;
                        $response[MyFamilyConstants::$DATA] = $data;
                    }
                    else {
                        $data = [MyFamilyConstants::$ERROR => _('The family member could not be saved.')];
                        $response[MyFamilyConstants::$CODE] = MyFamilyConstants::$FAILURE_CODE;
                        $response[MyFamilyConstants::$DATA] = $data;
                    }
                }
                else if ($cmd == MyFamilyConstants::$DELETE_FAMILY_MEMBER_CMD) {
                    $status = $this->delegate->deleteFamilyMember($user, $data);
                    if ($status) {
                        $data = [MyFamilyConstants::$MESSAGE => _('The family member has been deleted successfully.')];
                        $response[MyFamilyConstants::$CODE] = MyFamilyConstants::$SUCCESS_CODE;
                        $response[MyFamilyConstants::$DATA] = $data;
                    }
                    else {
                        $data = [MyFamilyConstants::$ERROR => _('The family member could not be deleted.')];
                        $response[MyFamilyConstants::$CODE] = MyFamilyConstants::$FAILURE_CODE;
                        $response[MyFamilyConstants::$DATA] = $data;
                    }
                }
                else if ($cmd == MyFamilyConstants::$EDIT_MEMBER_RESPONSES_CMD) {
                    $status = $this->delegate->saveMemberResponses($data);
                    if ($status) {
                        $data = [MyFamilyConstants::$MESSAGE => _('The response(s) have been saved successfully.')];
                        $response[MyFamilyConstants::$CODE] = MyFamilyConstants::$SUCCESS_CODE;
                        $response[MyFamilyConstants::$DATA] = $data;
                    }
                    else {
                        $data = [MyFamilyConstants::$ERROR => _('The response(s) could not be saved.')];
                        $response[MyFamilyConstants::$CODE] = MyFamilyConstants::$FAILURE_CODE;
                        $response[MyFamilyConstants::$DATA] = $data;
                    }
                }
                else if ($cmd == MyFamilyConstants::$GET_FAMILY_TREE_CMD) {
                    $familyTree = $this->delegate->getFamilyTree($data);
                    if ($familyTree) {
                        $response[MyFamilyConstants::$CODE] = MyFamilyConstants::$SUCCESS_CODE;
                        $response[MyFamilyConstants::$DATA] = $familyTree;
                    }
                    else {
                        $data = [MyFamilyConstants::$ERROR => _('The family tree could not be retrieved.')];
                        $response[MyFamilyConstants::$CODE] = MyFamilyConstants::$FAILURE_CODE;
                        $response[MyFamilyConstants::$DATA] = $data;
                    }
                }
                else if ($cmd == MyFamilyConstants::$EDIT_SETTINGS_CMD) {
                    $familyTree = $this->delegate->saveUserSettings($user, $data);
                    if ($familyTree) {
                        $data = [MyFamilyConstants::$MESSAGE => _('The settings have been saved successfully.')];
                        $response[MyFamilyConstants::$CODE] = MyFamilyConstants::$SUCCESS_CODE;
                        $response[MyFamilyConstants::$DATA] = $data;
                    }
                    else {
                        $data = [MyFamilyConstants::$ERROR => _('The settings could not be saved.')];
                        $response[MyFamilyConstants::$CODE] = MyFamilyConstants::$FAILURE_CODE;
                        $response[MyFamilyConstants::$DATA] = $data;
                    }
                }
                else {
                    $data = [MyFamilyConstants::$ERROR => _('Unsupported operation')];
                    $response[MyFamilyConstants::$CODE] = MyFamilyConstants::$FAILURE_CODE;
                    $response[MyFamilyConstants::$DATA] = $data;
                }
            }
        }
        catch(Exception $e) {
            $msg = (get_class($e) == 'LogicException') ? $e->getMessage() : _('An error has occurred.');
            if (get_class($e) == 'Facebook\Exceptions\FacebookResponseException') {
                $txt = $e->getMessage();
                if (stripos($txt, 'expired') != false) {
                    $msg = "The session has expired. Please refresh the browser page.";
                }
            }
            $data = [MyFamilyConstants::$ERROR => $msg];
            $response[MyFamilyConstants::$CODE] = (isset($user)) ? MyFamilyConstants::$FAILURE_CODE : MyFamilyConstants::$ACCESS_DENIED_CODE;
            $response[MyFamilyConstants::$DATA] = $data;
            $this->logger->error(sprintf("MyFamily::process(): Error occured while processing: %s\n%s", $e->getMessage(), $e->getTraceAsString()));
        }
        finally {
            if ($this->logger->isDebugEnabled()) {
                $this->logger->debug(sprintf("MyFamily::process(): Response: %s", json_encode($response)));
            }
            $this->delegate->close();
            $this->logger->close();
        }
        
        return $response;
    }
    
    /**
     * Reads the configuration file.
     */
    protected function readConfigFile() {
        $content = file_get_contents('./config.json');
        $config = json_decode($content, true);
        return $config;
    }
}
?>
