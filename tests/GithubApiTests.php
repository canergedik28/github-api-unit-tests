<?php
namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpClient\HttpClient;



class GithubApiTests extends WebTestCase
{
    private  $client;

    private $perPage = 100;

    private $friendsList;
    
    private $followersList;

    private $username = 'username';

    private $userFriendsCount;

    private $userFollowersCount;


    public function setUp():void{

        $this->client = HttpClient::create([
            'base_uri' => 'https://api.github.com/',
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer github_pat_token',
            ],
            'timeout' => 10,
        ]);

    } 

    public function userInfo(){
            $response = $this->client->request('GET', 'users/'.$this->username);
            $this->assertJson($response->getContent());
            $this->assertSame(200,$response->getStatusCode());
            $data = $response->toArray();
            $this->assertArrayHasKey('bio',$data);
            $this->assertArrayHasKey('name',$data);
            $this->assertArrayHasKey('followers',$data);
            $this->assertArrayHasKey('company',$data);
            $this->assertNotNull($data['bio']);
            $this->assertArrayHasKey('following',$data);
            $this->assertTrue($data['followers'] > 0);
            $this->assertTrue($data['following'] > 0);
            $this->userFriendsCount = $data['following'];
            $this->userFollowersCount = $data['followers'];
            $this->assertSame($this->userFriendsCount,$data['following']);
            $this->assertSame($this->userFollowersCount,$data['followers']);
    }


    public function userFollowers(){
                $followersData  = [];
                $this->assertTrue($this->userFollowersCount > 0);
                $pagesCount = ceil($this->userFollowersCount / $this->perPage);
                foreach (range(1,$pagesCount) as $page) {
                    $response = $this->client->request('GET', 'users/'.$this->username.'/followers',['query' => ['per_page' => $this->perPage, 'page' =>$page]]);
                    $this->assertJson($response->getContent());
                    $this->assertSame(200,$response->getStatusCode());
                    $data = $response->toArray();
                    $data = array_column($data,'login');
                    array_push($followersData,$data);
                }
                $newData =  array_merge(...$followersData);
                $this->followersList = $newData;
                $this->assertSame(count($this->followersList),$this->userFollowersCount);
        }


        public function userFriends(){
                $friendsData  = [];
                $this->assertTrue($this->userFriendsCount > 0);
                $pagesCount = ceil($this->userFriendsCount / $this->perPage);
                foreach (range(1,$pagesCount) as $page) {
                    $response = $this->client->request('GET', 'users/'.$this->username.'/following',['query' => ['per_page' => $this->perPage, 'page' =>$page]]);
                    $this->assertJson($response->getContent());
                    $this->assertSame(200,$response->getStatusCode());
                    $data = $response->toArray();
                    $data = array_column($data,'login');
                    array_push($friendsData,$data);
                }
                $newData =  array_merge(...$friendsData);
                $this->friendsList = $newData;
                $this->assertSame(count($this->friendsList),$this->userFriendsCount);
        }

        
        public function unfollowList(){
            $unfollowList = array_diff($this->friendsList,$this->followersList);
            $this->assertNotNull($unfollowList);
            $this->assertTrue(count($unfollowList)>0);
        }

        public function testGithubApi(){
            $this->userInfo();
            $this->userFollowers();
            $this->userFriends();
            $this->unfollowList();
        }

}




