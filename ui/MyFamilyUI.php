<?php
/**
 * Description: The class to provide UI logic.
 * 
 * @author Nitin Patil
 */
class MyFamilyUI {
    
    /** The logger. */
    private $logger;
    
    /** The current access token. */
    private $accessToken;
    
    
    /**
     * Initializes the UI.
     *
     * @param logger: The logger.
     */
    public function __construct($logger) {
        $this->logger = $logger;
    }
    
    /**
     * Returns the access token.
     */
    public function getAccessToken() {
        return $this->accessToken;
    }
    
    /**
     * Sets the access token.
     * 
     * @param accessToken: The access token to set.
     */
    public function setAccessToken($accessToken) {
        $this->accessToken = $accessToken;
    }
    
    /**
     * Shows the menu.
     */
    public function showMenu() {
?>
<form id="menuForm" method="post">
  <input type='hidden' id='<?php echo MyFamilyConstants::$CMD_PARAM; ?>' name='<?php echo MyFamilyConstants::$CMD_PARAM; ?>' value=''/>
  <input type='hidden' name='<?php echo MyFamilyConstants::$ACCESS_TOKEN_PARAM; ?>' value='<?php echo $this->getAccessToken(); ?>'/>
  <div class='menu'>
    <a class='menuItem'><?php echo _('User'); ?></a>
    <div class='subMenu'>
      <a class='menuAction' id='<?php echo MyFamilyConstants::$SHOW_EDIT_USER_FORM_CMD; ?>' href='#'><?php echo _('Edit User'); ?></a>
    </div>
    <a class='menuItem'><?php echo _('Family'); ?></a>
    <div class='subMenu'>
      <a class='menuAction' id='<?php echo MyFamilyConstants::$SHOW_ADD_FAMILY_FORM_CMD; ?>' href='#'><?php echo _('Add Family'); ?></a>
      <a class='menuAction' id='<?php echo MyFamilyConstants::$SHOW_MANAGE_FAMILIES_FORM_CMD; ?>' href='#'><?php echo _('Manage Families'); ?></a>
      <a class='menuAction' id='<?php echo MyFamilyConstants::$SHOW_ADD_FAMILY_MEMBER_FORM_CMD; ?>' href='#'><?php echo _('Add Member'); ?></a>
      <a class='menuAction' id='<?php echo MyFamilyConstants::$MANAGE_FAMILY_MEMBERS_CMD; ?>' href='#'><?php echo _('Manage Members'); ?></a>
    </div>
    <a class='menuAction' id='<?php echo MyFamilyConstants::$SHOW_FAMILY_TREE_FORM_CMD; ?>' href='#'><?php echo _('View Family Tree'); ?></a>
    <a class='menuAction' id='<?php echo MyFamilyConstants::$SHOW_SETTINGS_FORM_CMD; ?>' href='#'><?php echo _('Settings'); ?></a>
  </div>
</form>
<?php
    }
    
    /**
     * Shows a home message.
     * 
     * @param member: The member.
     */
    public function showHome($member) {
        $name = '';
        if ($member) {
            $name = $member['name'];
        }
        echo "<h1>Welcome $name!</h1>";
        echo "
<form>
  <table>
    <tr>
      <td>
Use the <strong>My Family</strong> app to know your family better and build stronger connections by following these simple steps.
<ul>
  <li><strong>Invite</strong> family members to use the <strong>My Family</strong> app.</li>
  <li><strong>Create a family</strong>. Don't forget to give it a cool name! For example, <i>The Legendary Patils</i>.<br/>
      <u>Tip</u>: You can create multiple families to organize better and sshhhh... keep family secrets.
  </li>
  <li><strong>Add members</strong> to your family. You can also enable few members to manage the family.</li>
  <li><strong>Add interesting facts</strong> about each member, such as, what was it like when they were growing up? What are their favorite movies?<br/>
      <u>Tip</u>: Only family members can see these details. So, feel free to share.
  </li>
  <li>View your awesome <strong>Family Tree</strong>.</li>
</ul>
Keep adding more details to your family and build stronger connections!
      </td>
    </tr>
  </table>
</form>
";
    }

    /**
     * Shows the edit user form.
     * 
     * @param cmd: The command.
     * @param genders: The genders.
     * @param user: The user.
     */
    public function showEditUserForm($cmd, $genders, $user) {
        $cmd = ($cmd == MyFamilyConstants::$SHOW_ADD_USER_FORM_CMD) ? MyFamilyConstants::$ADD_USER_CMD : MyFamilyConstants::$EDIT_USER_CMD;
        $id = (isset($user[MyFamilyConstants::$ID_PARAM])) ? $user[MyFamilyConstants::$ID_PARAM] : '';
        $name = (isset($user[MyFamilyConstants::$NAME_PARAM])) ? htmlspecialchars($user[MyFamilyConstants::$NAME_PARAM]) : '';
        $location = (isset($user[MyFamilyConstants::$LOCATION_PARAM])) ? htmlspecialchars($user[MyFamilyConstants::$LOCATION_PARAM]) : '';
        $gender = (isset($user[MyFamilyConstants::$GENDER_PARAM])) ? $user[MyFamilyConstants::$GENDER_PARAM] : '';
        $dob = (isset($user[MyFamilyConstants::$DOB_PARAM])) ? $user[MyFamilyConstants::$DOB_PARAM] : '';
        $gendersBuf = $this->prepareGenders($genders, $gender);
?>
<h1><?php echo _('Edit Profile'); ?></h1>
<form class='crudForm' id="userForm" method="post">
  <input type='hidden' name='<?php echo MyFamilyConstants::$CMD_PARAM; ?>' value='<?php echo $cmd; ?>'/>
  <input type='hidden' name='<?php echo MyFamilyConstants::$ACCESS_TOKEN_PARAM; ?>' value='<?php echo $this->getAccessToken(); ?>'/>
  <input type='hidden' name='<?php echo MyFamilyConstants::$ID_PARAM; ?>' value='<?php echo $id; ?>'/>
  <table>
<?php
        if ($user[MyFamilyConstants::$FB_ID_PARAM]) {
?>
    <tr>
      <td colspan='2'><center><img src='https://graph.facebook.com/<?php echo $user[MyFamilyConstants::$FB_ID_PARAM]; ?>/picture'/></center></td>
    </tr>
<?php
        }
?>
    <tr>
      <td><?php echo _('Name: '); ?></td>
      <td><input type='text' name='<?php echo MyFamilyConstants::$NAME_PARAM; ?>' value='<?php echo $name; ?>' placeholder='<?php echo _('Name'); ?>'/></td>
    </tr>
    <tr>
      <td><?php echo _('Location: '); ?></td>
      <td><input type='text' name='<?php echo MyFamilyConstants::$LOCATION_PARAM; ?>' value='<?php echo $location; ?>' placeholder='<?php echo _('Location'); ?>'/></td>
    </tr>
    <tr>
      <td><?php echo _('Gender: '); ?></td>
      <td><?php echo $gendersBuf; ?></td>
    </tr>
    <tr>
      <td><?php echo _('Birthday: '); ?></td>
      <td><input type='text' name='<?php echo MyFamilyConstants::$DOB_PARAM; ?>' value='<?php echo $dob; ?>' placeholder='<?php echo _('Birthday (yyyy-mm-dd)'); ?>'/></td>
    </tr>
    <tr>
      <td colspan='2'><center><input type='submit' name='submit' value='<?php echo _('Save'); ?>'/></center></td>
    </tr>
  </table>
</form>
<?php
    }
    
    /**
     * Shows the manage families form.
     *
     * @param user: The manager user.
     * @param families: The user's families.
     * @param familyID: The family ID to be selected, if any.
     */
    public function showManageFamiliesForm($user, $families, $familyID) {
        $managerID = (isset($user[MyFamilyConstants::$ID_PARAM])) ? $user[MyFamilyConstants::$ID_PARAM] : '';
        $familiesBuf = $this->prepareFamilies($families, $familyID);
        ?>
<h1><?php echo _('Manage Families'); ?></h1>
<form id="manageFamiliesForm" method="post">
  <input type='hidden' name='<?php echo MyFamilyConstants::$CMD_PARAM; ?>' value='<?php echo MyFamilyConstants::$SHOW_EDIT_FAMILY_FORM_CMD; ?>'/>
  <input type='hidden' name='<?php echo MyFamilyConstants::$ACCESS_TOKEN_PARAM; ?>' value='<?php echo $this->getAccessToken(); ?>'/>
  <input type='hidden' name='<?php echo MyFamilyConstants::$MANAGER_ID_PARAM; ?>' value='<?php echo $managerID; ?>'/>
  <table>
    <tr>
      <td><?php echo $familiesBuf; ?></td>
      <td>
        <input id='editFamily' type='submit' name='submit' value='<?php echo _('Modify'); ?>'/>
        <input id='deleteFamily' type='submit' name='submit' value='<?php echo _('Delete'); ?>'/>
      </td>
    </tr>
  </table>
</form>
<?php
    }
    
    /**
     * Shows the edit family form.
     * 
     * @param cmd: The command.
     * @param user: The manager user.
     * @param family: The family.
     */
    public function showEditFamilyForm($cmd, $user, $family) {
        $cmd = ($cmd == MyFamilyConstants::$SHOW_ADD_FAMILY_FORM_CMD) ? MyFamilyConstants::$ADD_FAMILY_CMD : MyFamilyConstants::$EDIT_FAMILY_CMD;
        $id = (isset($family[MyFamilyConstants::$ID_PARAM])) ? $family[MyFamilyConstants::$ID_PARAM] : '';
        $name = (isset($family[MyFamilyConstants::$NAME_PARAM])) ? htmlspecialchars($family[MyFamilyConstants::$NAME_PARAM]) : '';
        $managerID = (isset($user[MyFamilyConstants::$ID_PARAM])) ? $user[MyFamilyConstants::$ID_PARAM] : '';
?>
<h1><?php echo _('Edit Family'); ?></h1>
<form class='crudForm' id="familyForm" method="post">
  <input type='hidden' name='<?php echo MyFamilyConstants::$CMD_PARAM; ?>' value='<?php echo $cmd; ?>'/>
  <input type='hidden' name='<?php echo MyFamilyConstants::$ACCESS_TOKEN_PARAM; ?>' value='<?php echo $this->getAccessToken(); ?>'/>
  <input type='hidden' name='<?php echo MyFamilyConstants::$ID_PARAM; ?>' value='<?php echo $id; ?>'/>
  <input type='hidden' name='<?php echo MyFamilyConstants::$MANAGER_ID_PARAM; ?>' value='<?php echo $managerID; ?>'/>
  <table>
    <tr>
      <td><?php echo _('Family: '); ?></td>
      <td><input type='text' name='<?php echo MyFamilyConstants::$NAME_PARAM; ?>' value="<?php echo $name; ?>" placeholder='<?php echo _('Family name'); ?>'/></td>
    </tr>
    <tr>
      <td colspan='2'><center><input type='submit' name='submit' value='<?php echo _('Save'); ?>'/></center></td>
    </tr>
  </table>
</form>
<?php
    }
    
    /**
     * Shows the family members.
     * 
     * @param families: The families.
     * @parma familyID: The current family ID, if any.
     * @param members: The family members.
     */
    public function showFamilyMembers($families, $familyID, $members) {
        $familiesBuf = $this->prepareFamilies($families, $familyID);
?>
<h1>Manage Family Members</h1>
<form id='familyMemberSelectionForm'>
  <input type='hidden' id='<?php echo MyFamilyConstants::$CMD_PARAM; ?>' name='<?php echo MyFamilyConstants::$CMD_PARAM; ?>' value='<?php echo MyFamilyConstants::$MANAGE_FAMILY_MEMBERS_CMD; ?>'/>
  <input type='hidden' name='<?php echo MyFamilyConstants::$ACCESS_TOKEN_PARAM; ?>' value='<?php echo $this->getAccessToken(); ?>'/>
  <table>
    <tr>
      <td><?php echo $familiesBuf; ?></td>
      <td colspan='2'><center><input id='<?php echo MyFamilyConstants::$MANAGE_FAMILY_MEMBERS_CMD; ?>' type='submit' name='submit' value='<?php echo _('Show Members'); ?>'/></center></td>
    </tr>
  </table>
<?php
        if ($members) {
?>
  <div><p>&nbsp;</p></div>
  <input type='hidden' id='<?php echo MyFamilyConstants::$MEMBER_ID_PARAM; ?>' name='<?php echo MyFamilyConstants::$MEMBER_ID_PARAM; ?>' value=''/>
        <table class='dataTable'>
          <tr>
            <th>Name</th>
            <th>Action</th>
          </tr>
<?php
            foreach ($members as $member) {
                $id = $member[MyFamilyConstants::$MEMBER_ID_PARAM];
                $name = $member[MyFamilyConstants::$NAME_PARAM];
?>
          <tr>
            <td><?php echo $name; ?></td>
            <td>
              <a class='<?php echo MyFamilyConstants::$EDIT_FAMILY_MEMBER_CMD; ?>' id='<?php echo $id; ?>' href="#"><?php echo _('Edit Relation'); ?></a>
              | <a class='<?php echo MyFamilyConstants::$DELETE_FAMILY_MEMBER_CMD; ?>' id='<?php echo $id; ?>' href="#"><?php echo _('Delete'); ?></a>
              | <a class='<?php echo MyFamilyConstants::$EDIT_MEMBER_RESPONSES_CMD; ?>' id='<?php echo $id; ?>' href="#"><?php echo _('Details'); ?></a>
            </td>
          </tr>
<?php
            }
?>
        </table>
<?php
        }
?>
</form>
<?php
    }

    /**
     * Shows the edit family member form.
     * 
     * @param cmd: The command.
     * @param families: The families, if any.
     * @param familyID: The current family ID, if any.
     * @param members: The family members.
     * @param member: The seleted family member, if any.
     * @param relations: The list of relations.
     * @param friends: The current user friends.
     * @param managedUsers: The non-facebook users managed by the current user.
     */
    public function showEditFamilyMemberForm($cmd, $families, $familyID, $members, $member, $relations, $friends, $managedUsers) {
        $cmd = ($cmd == MyFamilyConstants::$SHOW_ADD_FAMILY_MEMBER_FORM_CMD) ? MyFamilyConstants::$ADD_FAMILY_MEMBER_CMD : MyFamilyConstants::$EDIT_FAMILY_MEMBER_CMD;
        $isUpdate = ($cmd == MyFamilyConstants::$EDIT_FAMILY_MEMBER_CMD) ? true : false;
        $cmd = ($familyID) ? $cmd : MyFamilyConstants::$SHOW_ADD_FAMILY_MEMBER_FORM_CMD;
        $familiesBuf = $this->prepareFamilies($families, $familyID, $isUpdate);
?>
<h1><?php echo _('Manage Family Members'); ?></h1>
<form id="familyMemberForm" method="post">
  <input type='hidden' name='<?php echo MyFamilyConstants::$CMD_PARAM; ?>' value='<?php echo $cmd; ?>'/>
  <input type='hidden' name='<?php echo MyFamilyConstants::$ACCESS_TOKEN_PARAM; ?>' value='<?php echo $this->getAccessToken(); ?>'/>
  <table>
    <tr>
      <td>
        <table>
          <tr>
            <td><?php echo $familiesBuf; ?></td>
<?php
        if (!$isUpdate) {
?>
            <td colspan='2'><center><input id='<?php echo MyFamilyConstants::$SHOW_ADD_FAMILY_MEMBER_FORM_CMD; ?>' type='submit' name='submit' value='<?php echo _('Manage'); ?>'/></center></td>
<?php
        }
        else {
?>
  <input type='hidden' name='<?php echo MyFamilyConstants::$ID_PARAM; ?>' value='<?php echo $member[MyFamilyConstants::$ID_PARAM]; ?>'/>
<?php
        }
?>
          </tr>
        </table>
      </td>
    </tr>
<?php
        if ($familyID) {
            $sourceMemberBuf = $this->prepareMembers(MyFamilyConstants::$SOURCE_MEMBER_ID_PARAM, $members);
            $relationsBuf = $this->prepareRelations($relations, null);
            $membersBuf = '';
            if ($isUpdate) {
                $memberID = $member[MyFamilyConstants::$MEMBER_ID_PARAM];
                $membersBuf = $this->prepareMembers(MyFamilyConstants::$MEMBER_ID_PARAM, $members, $memberID);
                if ($member[MyFamilyConstants::$IS_CREATOR_PARAM]) {
?>
  <input type='hidden' name='<?php echo MyFamilyConstants::$IS_CREATOR_PARAM; ?>' value='true'/>
<?php
                }
            }
            else {
                $membersBuf = "<select id='" . MyFamilyConstants::$FB_ID_PARAM . "' name='" . MyFamilyConstants::$FB_ID_PARAM . "'>\n";
                $membersBuf .= "<option value=''>" . _('-- Select a member --') . "</option>\n";
                foreach ($friends as $friend) {
                    $fbID = $friend[MyFamilyConstants::$ID_PARAM];
                    $name = htmlspecialchars($friend[MyFamilyConstants::$NAME_PARAM]);
                    $membersBuf .= "<option value='" . $fbID . "'>" . $name . "</option>\n";
                }
                $membersBuf .= "</select>\n";
            }
            $canManageFamilySelectedStr = ($member && $member[MyFamilyConstants::$CAN_MANAGE_FAMILY_PARAM] == 'Y') ? 'checked' : '';
            $isFamilyAdminSelectedStr = ($member && $member[MyFamilyConstants::$IS_FAMILY_ADMIN_PARAM] == 'Y') ? 'checked' : '';
            $saveButtonTitle = ($isUpdate) ? _('Save Member') : _('Add Member');
?>
    <tr>
      <td>
        <table>
          <tr>
            <td><?php echo $sourceMemberBuf; ?></td>
            <td>is a</td>
            <td><?php echo $relationsBuf; ?></td>
            <td>of</td>
            <td><?php echo $membersBuf; ?></td>
            <td><input type='submit' name='submit' value='<?php echo $saveButtonTitle; ?>'/></td>
          </tr>
          <tr>
            <td colspan='6'>
              <center><input type='checkbox' name='<?php echo MyFamilyConstants::$CAN_MANAGE_FAMILY_PARAM; ?>' value='Y' <?php echo $canManageFamilySelectedStr; ?>/> <?php echo _('Can manage family?'); ?>
              <input type='checkbox' name='<?php echo MyFamilyConstants::$IS_FAMILY_ADMIN_PARAM; ?>' value='Y' <?php echo $isFamilyAdminSelectedStr; ?>/> <?php echo _('Is family admin?'); ?></center>
            </td>
          </tr>
        </table>
      </td>
    </tr>
<?php
        }
?>
  </table>
</form>
<?php
    }
    
    /**
     * Get the questions control.
     * 
     * @param questsions: The questions.
     */
    public function getQuestionsControl($questions) {
        echo $this->prepareQuestions($questions);
    }
    
    /**
     * Shows the member responses form.
     * 
     * @param memberID: The member ID.
     * @param familyID: The family ID.
     * @param questions: The questions.
     * @param responses: The responses.
     */
    public function showMemberResponsesForm($memberID, $familyID, $questions, $responses) {
?>
<h1><?php echo _('Know Your Family Better!'); ?></h1>
<form id="getQuestionsForm" method="post">
  <input type='hidden' name='<?php echo MyFamilyConstants::$CMD_PARAM; ?>' value='<?php echo MyFamilyConstants::$GET_QUESTIONS_CONTROL_CMD; ?>'/>
  <input type='hidden' name='<?php echo MyFamilyConstants::$ACCESS_TOKEN_PARAM; ?>' value='<?php echo $this->getAccessToken(); ?>'/>
</form>

<p/>
<form class='crudForm' id="memberResponsesForm" method="post">
  <input type='hidden' name='<?php echo MyFamilyConstants::$CMD_PARAM; ?>' value='<?php echo MyFamilyConstants::$EDIT_MEMBER_RESPONSES_CMD; ?>'/>
  <input type='hidden' name='<?php echo MyFamilyConstants::$ACCESS_TOKEN_PARAM; ?>' value='<?php echo $this->getAccessToken(); ?>'/>
  <input type='hidden' name='<?php echo MyFamilyConstants::$MEMBER_ID_PARAM; ?>' value='<?php echo $memberID; ?>'/>
  <input type='hidden' name='<?php echo MyFamilyConstants::$FAMILY_ID_PARAM; ?>' value='<?php echo $familyID; ?>'/>
  <table class='actionBar'>
    <tr>
      <td>
        <center>
          <input type='button' id='addQuestion' value="<?php echo _('Add a Question'); ?>"/>
          <input type='submit' name='submit' value="<?php echo _('Save Responses'); ?>"/>
        </center>
      </td>
    </tr>
  </table>
  <table id='memberResponses'>
    <tbody>
<?php
        foreach ($responses as $response) {
            $questionID = $response[MyFamilyConstants::$QUESTION_ID_PARAM];
            $answer = htmlspecialchars($response[MyFamilyConstants::$RESPONSE_PARAM]);
            $questionsBuf = $this->prepareQuestions($questions, $questionID);
?>
      <tr>
        <td><?php echo $questionsBuf; ?></td>
      </tr>
      <tr>
        <td><textarea id='<?php echo MyFamilyConstants::$ANSWERS_PARAM; ?>' name='<?php echo MyFamilyConstants::$ANSWERS_PARAM; ?>[]' rows='5' cols='80'><?php echo $answer; ?></textarea></td>
      </tr>
<?php
        }
?>
    </tbody>
  </table>
</form>
<?php
    }
    
    /**
     * Shows the family tree form.
     *
     * @param families: The families, if any.
     * @param familyID: The family ID to be selected, if any.
     */
    public function showFamilyTreeForm($families, $familyID) {
        // Show family selection
        $familiesBuf = "<select id='" . MyFamilyConstants::$FAMILY_ID_PARAM . "' name='" . MyFamilyConstants::$FAMILY_ID_PARAM . "'>\n";
        $familiesBuf .= "<option value=''>" . _('-- Select a family --') . "</option>\n";
        foreach ($families as $family) {
            $id = $family[MyFamilyConstants::$FAMILY_ID_PARAM];
            $name = htmlspecialchars($family[MyFamilyConstants::$NAME_PARAM]);
            $selectedStr = ($familyID && $id == $familyID) ? 'selected' : '';
            $familiesBuf .= "<option value='" . $id . "' " . $selectedStr . ">" . $name . "</option>\n";
        }
        ?>
<h1><?php echo _('Family Tree'); ?></h1>
<form id="familyTreeForm" method="post">
  <input type='hidden' name='<?php echo MyFamilyConstants::$CMD_PARAM; ?>' value='<?php echo MyFamilyConstants::$GET_FAMILY_TREE_CMD; ?>'/>
  <input type='hidden' name='<?php echo MyFamilyConstants::$ACCESS_TOKEN_PARAM; ?>' value='<?php echo $this->getAccessToken(); ?>'/>
  <input type='hidden' id='<?php echo MyFamilyConstants::$MEMBER_ID_PARAM; ?>' name='<?php echo MyFamilyConstants::$MEMBER_ID_PARAM; ?>' value=''/>
  <table>
    <tr>
      <td><?php echo $familiesBuf; ?></td>
      <td colspan='2'><center><input type='submit' name='submit' value="<?php echo _('View Family Tree'); ?>"/></center></td>
    </tr>
  </table>
</form>
<div class='chart' id='familyTreeChart'></div>
<?php
    }
    
    /**
     * Shows the member details.
     * 
     * @param member: The member.
     * @param questions: The questions.
     * @param responses: The member responses.
     */
    public function showMemberDetails($member, $questions, $responses) {
        $name = $member[MyFamilyConstants::$NAME_PARAM] ? htmlspecialchars($member[MyFamilyConstants::$NAME_PARAM]) : '';
        if ($member[MyFamilyConstants::$DOB_PARAM]) {
            $dob = $member[MyFamilyConstants::$DOB_PARAM];
            $dob = DateTime::createFromFormat(MyFamilyConstants::$DATE_FORMAT, $dob)->format('d-M-Y');
        }
        else {
            $dob = '-';
        }
        $gender = $member[MyFamilyConstants::$GENDER_PARAM] ? $member[MyFamilyConstants::$GENDER_PARAM] : '-';
        $location = $member[MyFamilyConstants::$LOCATION_PARAM] ? htmlspecialchars($member[MyFamilyConstants::$LOCATION_PARAM]) : '-';
?>        
<h1><?php echo _('Member Information'); ?></h1>
<form>
  <table>
    <tr>
      <td>
        <table>
          <tr>
            <th><?php echo _('Name: '); ?></th>
            <td><?php echo $name; ?></td>
          </tr>
          <tr>
            <th><?php echo _('Birthday: '); ?></th>
            <td><?php echo $dob; ?></td>
          </tr>
          <tr>
            <th><?php echo _('Gender: '); ?></th>
            <td><?php echo $gender; ?></td>
          </tr>
          <tr>
            <th><?php echo _('Location: '); ?></th>
            <td><?php echo $location; ?></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</form>
<p/>
<h2><?php echo _('Details'); ?></h2>
<?php
        if ($responses) {
?>
<form>
  <table>
<?php
            foreach ($responses as $response) {
                $questionID = $response[MyFamilyConstants::$QUESTION_ID_PARAM];
                $answer = htmlspecialchars($response[MyFamilyConstants::$RESPONSE_PARAM]);
                $questionsBuf = $this->prepareQuestions($questions, $questionID, true);
?>
    <tr>
      <td><?php echo $questionsBuf; ?></td>
    </tr>
    <tr>
      <td><textarea id='<?php echo MyFamilyConstants::$ANSWERS_PARAM; ?>' name='<?php echo MyFamilyConstants::$ANSWERS_PARAM; ?>[]' rows='5' cols='80' readonly><?php echo $answer; ?></textarea></td>
    </tr>
<?php
            }
?>
  </table>
</form>
<?php
        }
        else {
?>
Add some details about them and know your family better.
<?php
        }
    }
    
    /**
     * Shows the user settings form.
     *
     * @param user: The user.
     * @param settings: The settings.
     * @param families: The user's families.
     */
    public function showUserSettingsForm($user, $settings, $families) {
        $familyID = $settings[MyFamilyConstants::$DEFAULT_FAMILY_SETTING];
        $familiesBuf = $this->prepareFamilies($families, $familyID, false, MyFamilyConstants::$DEFAULT_FAMILY_SETTING);
?>
<h1>User Settings</h1>
<form class='crudForm' id='settingsForm'>
  <input type='hidden' id='<?php echo MyFamilyConstants::$CMD_PARAM; ?>' name='<?php echo MyFamilyConstants::$CMD_PARAM; ?>' value='<?php echo MyFamilyConstants::$EDIT_SETTINGS_CMD; ?>'/>
  <input type='hidden' name='<?php echo MyFamilyConstants::$ACCESS_TOKEN_PARAM; ?>' value='<?php echo $this->getAccessToken(); ?>'/>
  <table>
    <tr>
      <td><?php echo _('Default Family: '); ?></td>
      <td><?php echo $familiesBuf; ?></td>
    </tr>
    <tr>
      <td colspan='2'><center><input type='submit' name='submit' value='<?php echo _('Save Settings'); ?>'/></center></td>
    </tr>
  </table>
</form>
<?php
    }
    
    /**
     * Prepares a list of families based on the supplied data.
     * 
     * @param families: The families.
     * @param familyID: (Optional) The family to be selected, if any.
     * @param disabled: (Optional) Whether the control should be disabled.
     * @param controlID: (Optional) The control ID.
     */
    protected function prepareFamilies($families, $familyID=null, $disabled=false, $controlID=null) {
        $disabledStr = ($disabled) ? 'disabled' : '';
        $controlID = ($controlID) ? $controlID : MyFamilyConstants::$FAMILY_ID_PARAM;
        $buf = "<select id='$controlID' name='$controlID' " . $disabledStr . ">\n";
        $buf .= "<option value=''>" . _('-- Select a family --') . "</option>\n";
        foreach ($families as $family) {
            $id = $family[MyFamilyConstants::$FAMILY_ID_PARAM];
            $name = htmlspecialchars($family[MyFamilyConstants::$NAME_PARAM]);
            $selectedStr = ($familyID && $id == $familyID) ? 'selected' : '';
            $buf .= "<option value='" . $id . "' " . $selectedStr .">" . $name . "</option>\n";
        }
        $buf .= "</select>\n";
        return $buf;
    }
    
    /**
     * Prepares a list of members based on the supplied data.
     * 
     * @param controlID: The control ID.
     * @param members: The members.
     * @param memberID: (Optional) The member to be selected, if any.
     */
    protected function prepareMembers($controlID, $members, $memberID=null) {
        $buf = "<select id='$controlID' name='$controlID'>\n";
        $buf .= "<option value=''>" . _('-- Select a member --') . "</option>\n";
        foreach ($members as $currMember) {
            $id = $currMember[MyFamilyConstants::$ID_PARAM];
            $name = htmlspecialchars($currMember[MyFamilyConstants::$NAME_PARAM]);
            $selectedStr = ($memberID && $id == $memberID) ? 'selected' : '';
            $buf .= "<option value='" . $id . "' " . $selectedStr . ">" . $name . "</option>\n";
        }
        $buf .= "</select>\n";
        return $buf;
    }
    
    /**
     * Prepares a list of relations. If a relation from the list was specified, it'll select it. 
     * 
     * @param genders: The list of genders.
     * @param gender: (Optional) The gender to be selected, if any.
     */
    protected function prepareGenders($genders, $gender=null) {
        $buf = "<select id='" . MyFamilyConstants::$GENDER_PARAM . "' name='" . MyFamilyConstants::$GENDER_PARAM . "'>\n";
        $buf .= "<option value=''>" . _('-- Select a gender --') . "</option>\n";
        foreach ($genders as $currGender) {
            $selectedStr = ($gender && $currGender == $gender) ? 'selected' : '';
            $buf .= "<option value='$currGender' $selectedStr>$currGender</option>\n";
        }
        $buf .= "</select>\n";
        return $buf;
    }
    
    /**
     * Prepares a list of genders. If a gender from the list was specified, it'll select it. 
     * 
     * @param relations: The list of relations.
     * @param relation: (Optional) The relation to be selected, if any.
     */
    protected function prepareRelations($relations, $relation=null) {
        $buf = "<select id='" . MyFamilyConstants::$RELATION_PARAM . "' name='" . MyFamilyConstants::$RELATION_PARAM . "'>\n";
        $buf .= "<option value=''>" . _('-- Select a relation --') . "</option>\n";
        foreach ($relations as $currRelation) {
            $selectedStr = ($relation && $currRelation == $relation) ? 'selected' : '';
            $buf .= "<option value='$currRelation' $selectedStr>" . strtolower($currRelation) . "</option>\n";
        }
        $buf .= "</select>\n";
        return $buf;
    }
    
    /**
     * Prepares the list of questions based on the supplied data.
     *
     * @param questsions: The questions.
     * @param questionID: (Optional) The question ID to be selected, if any.
     * @param disabled: (Optional) Whether the control is disabled.
     */
    protected function prepareQuestions($questions, $questionID=null, $disabled=false) {
        $disabledStr = ($disabled) ? 'disabled' : '';
        $buf = "<select id='" . MyFamilyConstants::$QUESTIONS_PARAM . "' name='" . MyFamilyConstants::$QUESTIONS_PARAM . "[]' $disabledStr>\n";
        $buf .= "<option value=''>" . _('-- Select a question --') . "</option>\n";
        foreach ($questions as $currQuestion) {
            $id = $currQuestion[MyFamilyConstants::$ID_PARAM];
            $question = htmlspecialchars($currQuestion[MyFamilyConstants::$QUESTION_PARAM]);
            $selectedStr = ($id == $questionID) ? 'selected' : '';
            $buf .= "<option value='$id' $selectedStr>" . $question . "</option>\n";
        }
        $buf .= "</select>\n";
        return $buf;
    }
}
?>
