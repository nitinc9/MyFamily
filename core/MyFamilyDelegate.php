<?php
/**
 * Description: The delegate class to provide core business logic.
 *
 * @author Nitin Patil
 */
class MyFamilyDelegate {
    
    /** The starting level. */
    private static $STARTING_LEVEL = 1000;
    
    /** The logger. */
    private $logger;
    
    /** The database host. */
    private $dbHost;
    
    /** The database user. */
    private $dbUser;
    
    /** The database password. */
    private $dbPassword;
    
    /** The current database connection. */
    private $con;
    
    
    /**
     * Initializes the delegate.
     *
     * @param config: The configuration.
     * @param logger: The logger.
     */
    public function __construct($config, $logger) {
        $this->logger = $logger;
        $this->dbHost = $config['dbHost'];
        $this->dbUser = $config['dbUser'];
        $this->dbName = $config['dbName'];
        $this->dbPassword = $config['dbPassword'];
    }
    
    /**
     * Prepares delegate for processing requests.
     */
    public function init() {
        $this->con = $this->getDBConnection();
    }
    
    /**
     * Clean up.
     */
    public function close() {
        $this->closeDBConnection($this->con);
    }
    
    /**
     * Returns the access token, if any.
     */
    public function getAccessToken() {
        return FBHelper::getAccessToken() ? FBHelper::getAccessToken() : $_POST[MyFamilyConstants::$ACCESS_TOKEN_PARAM];
    }
    
    /**
     * Sets the access token.
     *
     * @param accessToken: The access token.
     */
    public function setAccessToken($accessToken) {
        FBHelper::setAccessToken($accessToken);
    }
    
    /**
     * Returns a list of relations.
     */
    public function getRelations() {
        $relations = [MyFamilyConstants::$CHILD, MyFamilyConstants::$PARENT, MyFamilyConstants::$SPOUSE, MyFamilyConstants::$STEP_PARENT];
        return $relations;
    }
    
    /**
     * Returns a list of genders.
     */
    public function getGenders() {
        $genders = [MyFamilyConstants::$FEMALE, MyFamilyConstants::$MALE, MyFamilyConstants::$OTHER_GENDER];
        return $genders;
    }
    
    /**
     * Returns data about the current user.
     */
    public function getCurrentUser() {
        $user = null;
        $fbUser = FBHelper::getCurrentUser();
        if ($fbUser) {
            $this->logger->debug(sprintf("MyFamilyDelegate::getCurrentUser(): fbUser: %s", json_encode($fbUser)));
            $user = $this->findUserByFacebookID($fbUser['id']);
            // If user does not exist, create one
            if (!$user) {
                $data = [];
                $data[MyFamilyConstants::$FB_ID_PARAM] = $fbUser[MyFamilyConstants::$ID_PARAM];
                $data[MyFamilyConstants::$NAME_PARAM] = $fbUser[MyFamilyConstants::$NAME_PARAM];
                $data[MyFamilyConstants::$LOCATION_PARAM] = $fbUser[MyFamilyConstants::$LOCATION_PARAM];
                $data[MyFamilyConstants::$GENDER_PARAM] = $fbUser[MyFamilyConstants::$GENDER_PARAM];
                $data[MyFamilyConstants::$DOB_PARAM] = $fbUser[MyFamilyConstants::$DOB_PARAM];
                if ($this->saveUser(MyFamilyConstants::$ADD_USER_CMD, $data)) {
                    $user = $this->findUserByFacebookID($fbUser['id']);
                }
                else {
                    throw new LogicException(sprintf("Could not create a user for facebook user '%s' (fbID: %s)", $fbUser['name'], $fbUser['id']));
                }
            }
        }
        
        return $user;
    }
    
    /**
     * Returns data about the current user friends and managed users.
     * 
     * @param user: The current user.
     */
    public function getCurrentUserFriendsAndManagedUsers($user) {
        $connections = [];
        $connections[MyFamilyConstants::$FRIENDS] = FBHelper::getCurrentUserFriends();
        $connections[MyFamilyConstants::$MANAGED_USERS] = $this->getManagedUsers($user);
        
        if ($this->logger->isDebugEnabled()) {
            $this->logger->debug(sprintf("MyFamilyDelegate::getCurrentUserFriendsAndManagedUsers(): User ID: %s, connections: %s", $user[MyFamilyConstants::$ID_PARAM], json_encode($connections)));
        }
        
        return $connections;
    }
    
    /**
     * Returns the specified user's managed users.
     */
    public function getManagedUsers($user) {
        $id = $user[MyFamilyConstants::$ID_PARAM];
        $query = "select id as 'id', name as 'name', fb_id as 'fb_id', location as 'location', gender as 'gender', dob as 'dob' from user where manager_id=?";
        $params = [$id];
        $users = $this->executeDBQuery($query, $params, true);
        
        return $users;
    }
    
    /**
     * Retrieves the specified user.
     *
     * @param id: The user ID to lookup.
     */
    public function getUser($id) {
        $user = null;
        $query = "select id as 'id', name as 'name', fb_id as 'fb_id', location as 'location', gender as 'gender', dob as 'dob' from user where id=?";
        $params = [$id];
        $rows = $this->executeDBQuery($query, $params, true);
        if ($rows && count($rows) > 0) {
            $user = $rows[0];
        }
        $this->logger->debug(sprintf("MyFamilyDelegate::getUser(): id: %s, user: %s", $id, json_encode($user)));
        
        return $user;
    }
    
    /**
     * Finds the user by facebook ID.
     *
     * @param fbID: The facebook ID to use for the lookup.
     */
    public function findUserByFacebookID($fbID) {
        $user = null;
        $query = "select id as 'id', name as 'name', fb_id as 'fb_id', location as 'location', gender as 'gender', dob as 'dob' from user where fb_id=?";
        $params = [$fbID];
        $rows = $this->executeDBQuery($query, $params, true);
        if ($rows && count($rows) > 0) {
            $user = $rows[0];
        }
        $this->logger->debug(sprintf("MyFamilyDelegate::findUserByFacebookID(): fbID: %s, user: %s", $fbID, json_encode($user)));
        
        return $user;
    }
    
    /**
     * Saves a user based on the supplied data.
     *
     * @param cmd: The command.
     * @param data: The user data.
     *
     * @return Returns true if the user was saved successfully. Otherwise, false.
     */
    public function saveUser($cmd, $data) {
        $isUpdate = ($cmd == MyFamilyConstants::$EDIT_USER_CMD) ? true : false;
        $requiredParams = [MyFamilyConstants::$NAME_PARAM];
        if ($isUpdate) {
            $requiredParams[] = MyFamilyConstants::$ID_PARAM;
        }
        $missingParams = $this->checkRequiredParams($data, $requiredParams);
        if ($missingParams) {
            throw new LogicException($this->prepareMissingParamsMessage($missingParams));
        }
        $fbID = $data[MyFamilyConstants::$FB_ID_PARAM];
        $fbID = ($fbID) ? $fbID : null;
        $name = $data[MyFamilyConstants::$NAME_PARAM];
        $location = $data[MyFamilyConstants::$LOCATION_PARAM];
        $gender = $data[MyFamilyConstants::$GENDER_PARAM];
        $dob = $data[MyFamilyConstants::$DOB_PARAM];
        $managerID = $data[MyFamilyConstants::$MANAGER_ID_PARAM];
        $managerID = $managerID ? $managerID : null;
        $query = "insert into user(fb_id, name, location, gender, dob, manager_id) values(?, ?, ?, ?, ?, ?)";
        $params = [$fbID, $name, $location, $gender, $dob, $managerID];
        if ($isUpdate) {
            $id = $data[MyFamilyConstants::$ID_PARAM];
            $query = "update user set name=?, location=?, gender=?, dob=? where id=?";
            $params = [$name, $location, $gender, $dob, $id];
        }
        $status = $this->executeDBQuery($query, $params, false);
        $isSuccess = ($status > 0);
        
        return $isSuccess;
    }
    
    /**
     * Returns a list of families the specified user belongs to.
     * 
     * @param user: The user.
     */
    public function getUserFamilies($user) {
        $query = "select m.family_id as 'family_id', f.name as 'name' from family f, family_members m where f.id=m.family_id and m.member_id=?";
        $params = [$user[MyFamilyConstants::$ID_PARAM]];
        $families = $this->executeDBQuery($query, $params, true);
        return $families;
    }
    
    /**
     * Returns a list of families that the user can manage. If the 'isAdmin' is set to true, the user should also be an admin of the family.
     * 
     * @param user: The user.
     * @param isAdmin: (Optional) Whether the user should be an admin.
     */
    public function getUserManagedFamilies($user, $isAdmin=false) {
        $query = "select m.family_id as 'family_id', f.name as 'name' from family f, family_members m where f.id=m.family_id and m.member_id=? and m.can_manage_family=?";
        $params = [$user[MyFamilyConstants::$ID_PARAM], 'Y'];
        if ($isAdmin) {
            $query = "select m.family_id as 'family_id', f.name as 'name' from family f, family_members m where f.id=m.family_id and m.member_id=? and m.is_family_admin=?";
        }
        $families = $this->executeDBQuery($query, $params, true);
        return $families;
    }
    
    /**
     * Returns the family with the specified ID.
     * 
     * @param id: The family ID.
     */
    public function getFamily($id) {
        $family = null;
        if (!$id) {
            throw new LogicException("Please specify a family.");
        }
        $query = "select id as 'id', name as 'name' from family where id=?";
        $params = [$id];
        $rows = $this->executeDBQuery($query, $params, true);
        if ($rows) {
            $family = $rows[0];
        }
        
        return $family;
    }
    
    /**
     * Returns the family for the specified creator with the given name.
     * 
     * @param user: The user.
     * @param name: The family name to lookup.
     */
    public function getFamilyByUserAndName($user, $name) {
        $family = null;
        $creatorID = $user[MyFamilyConstants::$ID_PARAM];
        $query = "select id as 'id', name as 'name' from family where creator_id=? and name=?";
        $params = [$creatorID, $name];
        $rows = $this->executeDBQuery($query, $params, true);
        if ($rows) {
            $family = $rows[0];
        }
        
        return $family;
    }
    
    /**
     * Saves a family based on the supplied data.
     *
     * @param cmd: The command.
     * @param user: The family creator user.
     * @param data: The family data.
     *
     * @return Returns true if the family was saved successfully. Otherwise, false.
     */
    public function saveFamily($cmd, $user, $data) {
        $isUpdate = ($cmd == MyFamilyConstants::$EDIT_FAMILY_CMD) ? true : false;
        $requiredParams = [MyFamilyConstants::$NAME_PARAM];
        if ($isUpdate) {
            $requiredParams[] = MyFamilyConstants::$ID_PARAM;
        }
        $missingParams = $this->checkRequiredParams($data, $requiredParams);
        if ($missingParams) {
            throw new LogicException($this->prepareMissingParamsMessage($missingParams));
        }
        $id = $data[MyFamilyConstants::$ID_PARAM];
        $name = $data[MyFamilyConstants::$NAME_PARAM];
        // Check for duplicate family name
        $family = $this->getFamilyByUserAndName($user, $name);
        if ($family) {
            if (!$isUpdate || ($id != $family[MyFamilyConstants::$ID_PARAM])) {
                throw new LogicException("You already have another family with name: $name");
            }
        }
        $query = "insert into family(name, creator_id, creation_date) values(?, ?, sysdate())";
        $params = [$name, $user[MyFamilyConstants::$ID_PARAM]];
        if ($isUpdate) {
            // Update family
            $query = "update family set name=? where id=?";
            $params = [$name, $id];
        }
        $status = $this->executeDBQuery($query, $params, false);
        $isSuccess = ($status > 0);
        if ($isSuccess) {
            // Add the creator as the first family member
            if (!$isUpdate) {
                $family = $this->getFamilyByUserAndName($user, $name);
                $familyID = $family[MyFamilyConstants::$ID_PARAM];
                $this->logger->debug(sprintf("MyFamilyDelegate::saveFamily(): Family added successfully (id: %s), adding it's first member now: '%s'", $familyID, $user[MyFamilyConstants::$NAME_PARAM]));
                $memberData = [
                    MyFamilyConstants::$FAMILY_ID_PARAM         => $familyID,
                    MyFamilyConstants::$MEMBER_ID_PARAM         => $user[MyFamilyConstants::$ID_PARAM],
                    MyFamilyConstants::$LEVEL_PARAM             => MyFamilyDelegate::$STARTING_LEVEL,
                    MyFamilyConstants::$CAN_MANAGE_FAMILY_PARAM => 'Y',
                    MyFamilyConstants::$IS_FAMILY_ADMIN_PARAM   => 'Y'
                ];
                $isSuccess = $this->saveFamilyMember(MyFamilyConstants::$ADD_FAMILY_MEMBER_CMD, $memberData);
                if ($isSuccess) {
                    $this->logger->debug(sprintf("MyFamilyDelegate::saveFamily(): First member added successfully."));
                }
            }
        }
        
        return $isSuccess;
    }
    
    /**
     * Deletes the family based on the supplied data.
     *
     * @param data: The family data.
     */
    public function deleteFamily($data) {
        $requiredParams = [MyFamilyConstants::$FAMILY_ID_PARAM];
        $missingParams = $this->checkRequiredParams($data, $requiredParams);
        if ($missingParams) {
            throw new LogicException($this->prepareMissingParamsMessage($missingParams));
        }
        $familyID = $data[MyFamilyConstants::$FAMILY_ID_PARAM];
        // Ensure family is empty
        $query = "select count(*) as 'num_members' from family_members where family_id=?";
        $params = [$familyID];
        $rows = $this->executeDBQuery($query, $params, true);
        if ($rows && $rows[0]['num_members'] > 1) {
            throw new LogicException("The family contains more than one member. Please delete these first.");
        }
        // Delete the last member remaining
        $query = 'delete from family_members where family_id=?';
        $params = [$familyID];
        $status = $this->executeDBQuery($query, $params, false);
        // Delete the family
        $query = 'delete from family where id=?';
        $params = [$familyID];
        $status = $this->executeDBQuery($query, $params, false);
        $isSuccess = ($status > 0);
        
        return $isSuccess;
    }
    
    /**
     * Returns true if the specified family ID exists in the supplied list of families. Otherwise, false.
     * 
     * @param families: The families.
     * @param familyID: The family ID.
     */
    public function isFamilyInList($families, $familyID) {
        $exists = false;
        if ($families && $familyID) {
            foreach ($families as $family) {
                $id = $family[MyFamilyConstants::$FAMILY_ID_PARAM];
                if ($id == $familyID) {
                    $exists = true;
                    break;
                }
            }
        }
        return $exists;
    }
    /**
     * Returns the family members for the specified family.
     * 
     * @param familyID: The family ID.
     */
    public function getFamilyMembers($familyID) {
        $query = "select m.id as 'id', u.name as 'name', m.family_id as 'family_id', m.member_id as 'member_id', m.parent_id as 'parent_id', m.spouse_id as 'spouse_id', m.level as 'level', m.alias as 'alias',"
               . " m.can_manage_family as 'can_manage_family', m.is_family_admin as 'is_family_admin'"
               . " from family_members m, user u"
               . " where m.member_id=u.id and family_id=?"
               . " order by u.name";
        $params = [$familyID];
        $members = $this->executeDBQuery($query, $params, true);
        
        if ($this->logger->isDebugEnabled()) {
            $this->logger->debug(sprintf("MyFamilyDelegate::getFamilyMembers(): familyID: %s, members: %s", $familyID, json_encode($members)));
        }
        
        return $members;
    }
    
    /**
     * Returns the data about a family member based on the supplied data.
     * 
     * @param familyID: The family ID.
     * @param memberID: The member ID.
     */
    public function getFamilyMember($familyID, $memberID) {
        $member = null;
        $query = "select m.id as 'id', m.family_id as 'family_id', m.member_id as 'member_id', m.parent_id as 'parent_id', m.spouse_id as 'spouse_id', m.level as 'level', m.alias as 'alias',"
               . " m.can_manage_family as 'can_manage_family', m.is_family_admin as 'is_family_admin', f.creator_id as 'creator_id'"
               . " from family f, family_members m"
               . " where family_id=? and member_id=?";
        $params = [$familyID, $memberID];
        $rows = $this->executeDBQuery($query, $params, true);
        if ($rows) {
            $member = $rows[0];
            $member[MyFamilyConstants::$IS_CREATOR_PARAM] = ($memberID == $member[MyFamilyConstants::$CREATOR_ID_PARAM]);
        }
        
        if ($this->logger->isDebugEnabled()) {
            $this->logger->debug(sprintf("MyFamilyDelegate::getFamilyMember(): familyID: %s, memberID: %s, member: %s", $familyID, $memberID, json_encode($member)));
        }
        
        return $member;
    }
    
    /**
     * Saves a family member on the supplied data.
     *
     * @param cmd: The command.
     * @param data: The member data.
     * 
     * This method computes the family member's level/order in the family, based on the following logic.
     * - It uses the sourceMember's relation to the specified member. Read relation as (sourceMember -> member). E.g., sourceMember is a "parent of" member.
     * - if (relation == (step) parent)
     *     - Add member (level=(sourceMember->level + 1), parent_id=sourceMemberID, spouse_id=null).
     * - Else if (relation == child)
     *     - Add member (level=(sourceMember->level - 1), parent_id=null, spouse_id=null)
     *     - Update the sourceMember->parent_id to member_id.
     * - Else if (relation == spouse)
     *     - Add member (level=sourceMember->level, parent_id=null, spouse_id=sourceMemberID)
     *     - Update the sourceMember->spouse_id to member_id.
     *
     * @return Returns true if the family member was saved successfully. Otherwise, false.
     */
    public function saveFamilyMember($cmd, $data) {
        $isUpdate = ($cmd == MyFamilyConstants::$EDIT_FAMILY_MEMBER_CMD) ? true : false;
        $requiredParams = [MyFamilyConstants::$FAMILY_ID_PARAM];
        $requiredParamLabels = [MyFamilyConstants::$FAMILY_ID_PARAM => _('family'), MyFamilyConstants::$SOURCE_MEMBER_ID_PARAM => _('Source Member')];
        if ($isUpdate) {
            $requiredParams[] = MyFamilyConstants::$ID_PARAM;
        }
        else {
            // If level not specified (i.e., it's not the first member), ensure required parameters have been provided
            if (!$data[MyFamilyConstants::$LEVEL_PARAM]) {
                $requiredParams[] = MyFamilyConstants::$SOURCE_MEMBER_ID_PARAM;
                $requiredParams[] = MyFamilyConstants::$RELATION_PARAM;
            }
        }
        $missingParams = $this->checkRequiredParams($data, $requiredParams);
        if ($missingParams) {
            throw new LogicException($this->prepareMissingParamsMessage($missingParams));
        }
        $relation = $data[MyFamilyConstants::$RELATION_PARAM];
        $familyID = $data[MyFamilyConstants::$FAMILY_ID_PARAM];
        $sourceMemberID = $data[MyFamilyConstants::$SOURCE_MEMBER_ID_PARAM];
        $memberID = $data[MyFamilyConstants::$MEMBER_ID_PARAM];
        $fbID = $data[MyFamilyConstants::$FB_ID_PARAM];
        $isCreator = $data[MyFamilyConstants::$IS_CREATOR_PARAM];
        $level = null;
        $parentID = null;
        $spouseID = null;
        $sourceMemberParentID = null;
        $sourceMemberSpouseID = null;
        if (!$memberID && !$fbID) {
            throw new LogicException("Either a valid app user or a facebook friend must be specified.");
        }
        // Lookup member, if needed
        if (!$memberID) {
            $member = $this->findUserByFacebookID($fbID);
            if (!$member) {
                throw new LogicException("No member found with facebook ID: $fbID");
            }
            $memberID = $member[MyFamilyConstants::$ID_PARAM];
        }
        
        // Compute hierarchy and relations based on the data supplied
        $this->logger->debug(sprintf("MyFamilyDelegate::saveFamilyMember(): Lookup source member with id: %s", $sourceMemberID));
        $sourceMember = $this->getFamilyMember($familyID, $sourceMemberID);
        $updateHierarchyAndRelations = false;
        if ($relation == MyFamilyConstants::$PARENT || $relation == MyFamilyConstants::$STEP_PARENT) {
            $parentID = $sourceMemberID;
            $level = $sourceMember[MyFamilyConstants::$LEVEL_PARAM] + 1;
            $updateHierarchyAndRelations = true;
        }
        else if ($relation == MyFamilyConstants::$CHILD) {
            $sourceMemberParentID = $memberID;
            $level = $sourceMember[MyFamilyConstants::$LEVEL_PARAM] - 1;
            $updateHierarchyAndRelations = true;
        }
        else if ($relation == MyFamilyConstants::$SPOUSE) {
            $spouseID = $sourceMemberID;
            $sourceMemberSpouseID = $memberID;
            $level = $sourceMember[MyFamilyConstants::$LEVEL_PARAM];
            $updateHierarchyAndRelations = true;
        }
        $level = $data[MyFamilyConstants::$LEVEL_PARAM] ? $data[MyFamilyConstants::$LEVEL_PARAM] : $level;
        $alias = $data[MyFamilyConstants::$ALIAS_PARAM];
        $canManageFamily = $data[MyFamilyConstants::$CAN_MANAGE_FAMILY_PARAM];
        $canManageFamily = ($canManageFamily || $isCreator) ? 'Y' : 'N';
        $isFamilyAdmin = $data[MyFamilyConstants::$IS_FAMILY_ADMIN_PARAM];
        $isFamilyAdmin = ($isFamilyAdmin || $isCreator) ? 'Y' : 'N'; // A creator is always an admin
        $canManageFamily = ($isFamilyAdmin == 'Y') ? 'Y' : $canManageFamily;
        $query = "insert into family_members(family_id, member_id, parent_id, spouse_id, level, alias, can_manage_family, is_family_admin) values(?, ?, ?, ?, ?, ?, ?, ?)";
        $params = [$familyID, $memberID, $parentID, $spouseID, $level, $alias, $canManageFamily, $isFamilyAdmin];
        if ($isUpdate) {
            $id = $data[MyFamilyConstants::$ID_PARAM];
            if ($updateHierarchyAndRelations) {
                $query = "update family_members set parent_id=?, spouse_id=?, level=?, alias=?, can_manage_family=?, is_family_admin=? where id=?";
                $params = [$parentID, $spouseID, $level, $alias, $canManageFamily, $isFamilyAdmin, $id];
            }
            else {
                $query = "update family_members set alias=?, can_manage_family=?, is_family_admin=? where id=?";
                $params = [$alias, $canManageFamily, $isFamilyAdmin, $id];
            }
        }
        $status = $this->executeDBQuery($query, $params, false);
        $isSuccess = ($status > 0);
        
        // Check if source member needs an update
        if ($isSuccess && $updateHierarchyAndRelations && ($sourceMemberParentID || $sourceMemberSpouseID)) {
            if ($sourceMemberParentID) {
                $this->logger->debug(sprintf("MyFamilyDelegate::saveFamilyMember(): Updating parent of source member id: %s, parentID: %s", $sourceMemberID, $sourceMemberParentID));
                $query = "update family_members set parent_id=? where family_id=? and member_id=?";
                $params = [$sourceMemberParentID, $familyID, $sourceMemberID];
                $status = $this->executeDBQuery($query, $params, false);
            }
            else if ($sourceMemberSpouseID) {
                $this->logger->debug(sprintf("MyFamilyDelegate::saveFamilyMember(): Updating spouse of source member id: %s, spouseID: %s", $sourceMemberID, $sourceMemberSpouseID));
                $query = "update family_members set spouse_id=? where family_id=? and member_id=?";
                $params = [$sourceMemberSpouseID, $familyID, $sourceMemberID];
                $status = $this->executeDBQuery($query, $params, false);
            }
            $isSuccess = ($status > 0);
        }
        
        return $isSuccess;
    } // saveFamilyMember() ENDS
    
    /**
     * Deletes the family member based on the supplied data.
     *
     * @param user: The current user.
     * @param data: The member data.
     */
    public function deleteFamilyMember($user, $data) {
        $requiredParams = [MyFamilyConstants::$FAMILY_ID_PARAM, MyFamilyConstants::$MEMBER_ID_PARAM];
        $missingParams = $this->checkRequiredParams($data, $requiredParams);
        if ($missingParams) {
            throw new LogicException($this->prepareMissingParamsMessage($missingParams));
        }
        $familyID = $data[MyFamilyConstants::$FAMILY_ID_PARAM];
        $memberID = $data[MyFamilyConstants::$MEMBER_ID_PARAM];
        $currentMemberID = $user[MyFamilyConstants::$ID_PARAM];
        // Ensure family member sub-tree is empty
        $query = "select count(*) as 'num_members' from family_members where family_id=? and parent_id=? and member_id != ?";
        $params = [$familyID, $memberID, $currentMemberID];
        $rows = $this->executeDBQuery($query, $params, true);
        if ($rows && $rows[0]['num_members'] > 0) {
            throw new LogicException("The member has other child members. Please delete these child members first.");
        }
        $query = 'delete from family_members where family_id=? and member_id=? and member_id != ?';
        $params = [$familyID, $memberID, $currentMemberID];
        $status = $this->executeDBQuery($query, $params, false);
        $isSuccess = ($status > 0);
        
        return $isSuccess;
    }
    
    /**
     * Retrieves all the questions.
     */
    public function getQuestions() {
        $query = "select id as 'id', question as 'question' from question";
        return $this->executeDBQuery($query, null, true);
    }
    
    /**
     * Retrieves all the member questions and corresponding responses.
     * 
     * @param memberID: The member ID.
     * @param familyID: The family ID.
     */
    public function getMemberResponses($memberID, $familyID) {
        $query = "select q.id as 'question_id', q.question as 'question', r.response from question q, member_responses r where q.id=r.question_id and r.member_id=? and r.family_id=?";
        $params = [$memberID, $familyID];
        $responses = $this->executeDBQuery($query, $params, true);
        
        if ($this->logger->isDebugEnabled()) {
            $this->logger->debug(sprintf("MyFamilyDelegate::getMemberResponses(): memberID: %s, familyID: %s, responses: %s", $memberID, $familyID, json_encode($responses)));
        }
        
        return $responses;
    }
    
    /**
     * Retrieves the member response to specified question.
     * 
     * @param memberID: The member ID.
     * @param familyID: The family ID.
     * @param questionID: The question ID.
     */
    public function getMemberResponseToQuestion($memberID, $familyID, $questionID) {
        $response = null;
        $query = "select q.id as 'question_id', q.question as 'question', r.response as 'response' from question q, member_responses r where q.id=r.question_id and r.member_id=? and r.family_id=? and r.question_id=?";
        $params = [$memberID, $familyID, $questionID];
        $rows = $this->executeDBQuery($query, $params, true);
        if ($rows) {
            $response = $rows[0];
        }
        
        return $response;
    }
    
    /**
     * Saves the member responses to questions.
     * 
     * @param data: The response data.
     */
    public function saveMemberResponses($data) {
        $status = 0;
        $requiredParams = [MyFamilyConstants::$FAMILY_ID_PARAM, MyFamilyConstants::$MEMBER_ID_PARAM, MyFamilyConstants::$QUESTIONS_PARAM, MyFamilyConstants::$ANSWERS_PARAM];
        $missingParams = $this->checkRequiredParams($data, $requiredParams);
        if ($missingParams) {
            throw new LogicException($this->prepareMissingParamsMessage($missingParams));
        }
        $familyID = $data[MyFamilyConstants::$FAMILY_ID_PARAM];
        $memberID = $data[MyFamilyConstants::$MEMBER_ID_PARAM];
        $questions = $data[MyFamilyConstants::$QUESTIONS_PARAM];
        $answers = $data[MyFamilyConstants::$ANSWERS_PARAM];
        if (count($questions) != count($answers)) {
            throw new LogicException("There should be same number of questions and answers.");
        }
        $i = 0;
        foreach ($questions as $questionID) {
            // Check if the member has previously responded to this question
            $response = $this->getMemberResponseToQuestion($memberID, $familyID, $questionID);
            $query = "insert into member_responses(member_id, family_id, question_id, response) values(?, ?, ?, ?)";
            $params = null;
            if ($response) {
                // Update member response
                $query = "update member_responses set response=? where member_id=? and family_id=? and question_id=?";
                $params = [$answers[$i], $memberID, $familyID, $questionID];
            }
            else {
                $params = [$memberID, $familyID, $questionID, $answers[$i]];
            }
            $status = $this->executeDBQuery($query, $params, false);
            $i++;
        }
        $isSuccess = ($status > 0);
        
        return $isSuccess;
    }
    
    /**
     * Returns the family tree for the specified data.
     *
     * @param data: The request data.
     */
    public function getFamilyTree($data) {
        $familyTree = [];
        $familyID = $data[MyFamilyConstants::$FAMILY_ID_PARAM];
        if (!$familyID) {
            throw new LogicException(sprintf('Please select a family.'));
        }
        $query = "select u.id as 'member_id', u.name as 'name', u.fb_id as 'fb_id', u.location as 'location', u.dob as 'dob', m.parent_id as 'parent_id', m.spouse_id as 'spouse_id', m.level as 'level'"
               . " from family_members m, user u"
               . " where m.member_id = u.id and m.family_id=?"
               . " group by m.parent_id, m.level, u.id, u.name, u.location, u.dob"
               . " order by m.level, m.id";
        $params = [$familyID];
        $rows = $this->executeDBQuery($query, $params, true);
        if ($rows) {
            $familyTree = $rows;
        }
        
        if ($this->logger->isDebugEnabled()) {
            $this->logger->debug(sprintf("MyFamilyDelegate::getFamilyTree(): familyID: %s, familyTree: %s", $familyID, json_encode($familyTree)));
        }
        
        return $familyTree;
    }
    
    /**
     * Returns the user settings.
     * 
     * @param user: The user.
     */
    public function getUserSettings($user) {
        $settings = [];
        $query = "select setting as 'setting', value as 'value' from settings where user_id=?";
        $params = [$user[MyFamilyConstants::$ID_PARAM]];
        $rows = $this->executeDBQuery($query, $params, true);
        foreach ($rows as $row) {
            $key = $row[MyFamilyConstants::$SETTING_PARAM];
            $value = $row[MyFamilyConstants::$VALUE_PARAM];
            $settings[$key] = $value;
        }
        return $settings;
    }
    
    /**
     * Returns the specified user setting.
     * 
     * @param user: The user.
     * @param key: The setting key to lookup.
     */
    public function getUserSetting($user, $key) {
        $setting = null;
        $userID = $user[MyFamilyConstants::$ID_PARAM];
        $query = "select setting as 'setting', value as 'value' from settings where user_id=? and setting=?";
        $params = [$userID, $key];
        $rows = $this->executeDBQuery($query, $params, true);
        if ($rows) {
            $setting = $rows[0];
        }
        
        return $setting;
    }
    
    /**
     * Returns the default family ID for the user.
     * 
     * @param user: The user.
     */
    public function getUserDefaultFamilyID($user) {
        $familyID = null;
        $setting = $this->getUserSetting($user, MyFamilyConstants::$DEFAULT_FAMILY_SETTING);
        if ($setting) {
            $familyID = $setting[MyFamilyConstants::$VALUE_PARAM];
        }
        
        return $familyID;
    }
    
    /**
     * Saves user settings.
     * 
     * @param user: The user.
     * @param data: The settings data.
     */
    public function saveUserSettings($user, $data) {
        $settings = [MyFamilyConstants::$DEFAULT_FAMILY_SETTING];
        $missingParams = $this->checkRequiredParams($data, $settings);
        if ($missingParams) {
            throw new LogicException($this->prepareMissingParamsMessage($missingParams));
        }
        $userID = $user[MyFamilyConstants::$ID_PARAM];
        $i = 0;
        $status = 0;
        foreach ($settings as $key) {
            $setting = $this->getUserSetting($user, $key);
            $value = $data[$key];
            $query = "insert into settings(user_id, setting, value) values(?, ?, ?)";
            $params = [$userID, $key, $value];
            if ($setting) {
                $query = "update settings set value=? where user_id=? and setting=?";
                $params = [$value, $userID, $key];
            }
            $status = $this->executeDBQuery($query, $params, false);
            $i++;
            $this->logger->debug(sprintf("MyFamilyDelegate::saveUserSettings(): userID: %s, setting: %s, value: %s", $userID, $key, $value));
        }
        $isSuccess = ($status > 0);
        
        return $isSuccess;
    }
    
    /**
     * Returns a database connection.
     */
    protected function getDBConnection() {
        $con = null;
        $host = $this->dbHost;
        $db = $this->dbName;
        $con = new PDO("mysql:host=$host;dbname=$db", $this->dbUser, $this->dbPassword);
        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->logger->debug(sprintf("MyFamilyDelegate::getDBConnection(): Acquired db connection for %s@$host:$db", $this->dbUser));
        
        return $con;
    }
    
    /**
     * Closes the supplied database connection.
     *
     * @param con: The connection to close.
     */
    protected function closeDBConnection(&$con) {
        $con = null;
    }
    
    /**
     * Executes a database query with the specified parameters, if any.
     *
     * @param query: The query to execute.
     * @param params: The query parameters, if any.
     * @param returnResults: Whether to return results. Use this for queries that are expected to return results.
     *
     * @return Returns the result of the query execution.
     */
    protected function executeDBQuery($query, $params, $returnResults) {
        if ($this->logger->isDebugEnabled()) {
            $this->logger->debug(sprintf("MyFamilyDelegate::executeDBQuery(): query: %s, params: %s", $query, json_encode($params)));
        }
        $stmt = $this->con->prepare($query);
        $index = 1;
        if ($params) {
            foreach ($params as $param) {
                $stmt->bindValue($index++, $param);
            }
        }
        $retval = $stmt->execute();
        if ($returnResults) {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $rows;
        }
        
        return $retval;
    }
    
    /**
     * Checks for required parameters in the supplied data.
     *
     * @param data: The data to check.
     * @param requiredParams: The required parameters.
     *
     * @return Returns a list of required parameters that do not exist in data, if any.
     */
    protected function checkRequiredParams($data, $requiredParams) {
        $missingParams = [];
        foreach ($requiredParams as $param) {
            if (!isset($data[$param]) || !$data[$param]) {
                $missingParams[] = $param;
            }
        }
        if ($this->logger->isDebugEnabled()) {
            $this->logger->debug(sprintf("MyFamilyDelegate::checkRequiredParams(): data: %s, requiredParams: %s, missingParams: %s", json_encode($data), json_encode($requiredParams), json_encode($missingParams)));
        }
        return $missingParams;
    }
    
    /**
     * Prepares a more user-friendly message for missing parameters.
     * 
     * @param missingParams: The list of missing params.
     */
    protected function prepareMissingParamsMessage($missingParams) {
        $paramLabels = [
            MyFamilyConstants::$NAME_PARAM             => _('Family'),
            MyFamilyConstants::$FAMILY_ID_PARAM        => _('Family'),
            MyFamilyConstants::$SOURCE_MEMBER_ID_PARAM => _('Source Member'),
            MyFamilyConstants::$RELATION_PARAM         => _('Relation'),
            MyFamilyConstants::$MEMBER_ID_PARAM        => _('Member'),
            MyFamilyConstants::$QUESTIONS_PARAM        => _('Questions'),
            MyFamilyConstants::$ANSWERS_PARAM          => _('Answers'),
            MyFamilyConstants::$DEFAULT_FAMILY_SETTING => _('Default Family')
        ];
        
        $params = [];
        foreach ($missingParams as $param) {
            if (isset($paramLabels[$param])) {
                $params[] = $paramLabels[$param];
            }
            else {
                $params[] = $param;
            }
        }
        return sprintf('Following required data is mising: %s', json_encode($params));
    }
}
?>
