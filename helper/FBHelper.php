<?php
/**
 * Description: Facebook Helper class.
 * 
 * @author Nitin Patil
 */
class FBHelper {
    
    /** The facebook handle. */
    private static $FB;
    
    /** The access token. */
    private static $AccessToken;
    
    
    /**
     * Returns current user data.
     */
    public static function getCurrentUser() {
        $user = [];
        FBHelper::acquireAccessToken();
        $fb = FBHelper::get_fb();
        $response = $fb->get('/me?fields=id,name,gender,birthday,location');
        $fbUser = $response->getGraphUser();
        if ($fbUser) {
            $location = $fbUser->getLocation();
            if ($location) {
                $location = $location->getName();
            }
            $dob = $fbUser->getBirthday();
            if ($dob) {
                $dob = $dob->format(MyFamilyConstants::$DATE_FORMAT);
            }
            $user = [
                MyFamilyConstants::$ID_PARAM => $fbUser->getId(),
                MyFamilyConstants::$NAME_PARAM => $fbUser->getName(),
                MyFamilyConstants::$LOCATION_PARAM => $location,
                MyFamilyConstants::$GENDER_PARAM => $fbUser->getGender(),
                MyFamilyConstants::$DOB_PARAM => $dob
            ];
        }
        
        return $user;
    }
    
    /**
     * Returns current user friends.
     */
    public static function getCurrentUserFriends() {
        $user = [];
        FBHelper::acquireAccessToken();
        $fb = FBHelper::get_fb();
        $response = $fb->get('/me/friends')->getDecodedBody();
        $friends = isset($response['data']) ? $response['data'] : [];
        
        return $friends;
    }
    
    /**
     * Returns the current access token, if any. If none exists, it tries to acquire one.
     */
    public static function getAccessToken() {
        if (!FBHelper::$AccessToken) {
            FBHelper::acquireAccessToken();
        }
        return FBHelper::$AccessToken;
    }
    
    /**
     * Sets the access token to the specified one.
     * 
     * @param accessToken: The access token to set.
     */
    public static function setAccessToken($accessToken) {
        FBHelper::$AccessToken = $accessToken;
    }

    /**
     * Checks whether a valid access token exists and sets it as the default token to use. If not, it attempts to login and acquire one.
     */
    protected static function acquireAccessToken() {
        $fb = FBHelper::get_fb();
        $accessToken = FBHelper::$AccessToken;
        if (!$accessToken) {
            $helper = $fb->getCanvasHelper();
            $accessToken = $helper->getAccessToken();
        }
        if ($accessToken) {
            FBHelper::setAccessToken($accessToken);
            $fb->setDefaultAccessToken($accessToken);
        }
    }
    
    /**
     * Returns the facebook handle.
     */
    protected static function get_fb() {
        if (!FBHelper::$FB) {
            FBHelper::$FB = new Facebook\Facebook([
                'app_id' => '233351993945655',
                'app_secret' => 'e1883a5c94551f28749100e31a17f41f',
                'default_graph_version' => 'v2.2'
            ]);
        }
        return FBHelper::$FB;
    }
}
?>
