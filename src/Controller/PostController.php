<?php

namespace App\Controller;

use App\Entity\Post;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PostController
 * @package App\Controller
 * @Route("/api", name="post_api")
 */
class PostController extends AbstractController
{
    /**
     * @param int $userId
     * @param UserRepository $userRepository
     * @return JsonResponse
     * @Route("/users/{userId}/posts", name="posts", methods={"GET"})
     */
    public function getPosts(int $userId, UserRepository $userRepository)
    {
        $response = new JsonResponse();
        $user = $userRepository->findOneBy(['id' => $userId]);
        $posts = [];
        if (!empty($user)) {
            $posts = $user->getPosts();
            if(empty($posts))
            {
                $data = [
                    'status' => 404,
                    'errors' => "Posts not found",
                ];
                return $this->response($data, 404);
            }
        } else {
            $response->setData(
                [
                    'status' => '404',
                    'errors' => 'User not found',
                ]
            );
            $response->setStatusCode(404);
            return $response;
        }
        $data = [];
        foreach ($posts as $post) {

            $data[] =
                [
                    'id' => $post->getId(),
                    'name' => $post->getName(),
                    'description' => $post->getDescription(),
                ];
        }
        if (empty($data)) {
            $response->setData(
                [
                    'status' => '404',
                    'errors' => 'Posts not found',
                ]
            );
            $response->setStatusCode(404);
            return $response;
        }

        return $response->setData($data);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     * @param int $userId
     * @return JsonResponse
     * @throws \Exception
     * @Route("/users/{userId}/posts", name="posts_add", methods={"POST"})
     */
    public function addPost(Request $request, EntityManagerInterface $entityManager,
                            UserRepository $userRepository, int $userId
    )
    {
        try {
            $request = $this->transformJsonBody($request);
            if (!$request || !$request->get('name') || !$request->request->get('description')) {
                throw new \Exception();
            }

            $user = $userRepository->findOneBy(['id' => $userId]);

            if (!empty($user)) {

                $post = new Post();
                $post->setUser($user);
                $post->setName($request->get('name'));
                $post->setDescription($request->get('description'));
                $entityManager->persist($post);
                $entityManager->flush();

                $data = [
                    'status' => 201,
                    'success' => "Post added successfully",
                ];
                return $this->response($data,201);
            } else {
                $data = [
                    'status' => 404,
                    'errors' => "User not found",
                ];
                return $this->response($data, 404);
            }

        } catch (\Exception $e) {
            $data = [
                'status' => 422,
                'errors' => "Data no valid",
            ];
            return $this->response($data, 422);
        }
    }

    /**
     * @param PostRepository $postRepository
     * @param $postId
     * @param int $userId
     * @return JsonResponse
     * @Route("/users/{userId}/posts/{postId}", name="posts_get", methods={"GET"})
     */
    public function getPost(PostRepository $postRepository, int $postId, int $userId)
    {
        $post = $postRepository->find($postId);
        $response = new JsonResponse();

        if (!$post) {
            $data = [
                'status' => 404,
                'errors' => "Post not found",
            ];
            return $this->response($data, 404);
        } else {
            if ($post->getUser()->getId() == $userId) {
                $response->setData(
                    [
                        'id' => $post->getId(),
                        'name' => $post->getName(),
                        'description' => $post->getDescription(),
                    ]
                );
                $response->setStatusCode(200);
            } else {
                $data = [
                    'status' => 404,
                    'errors' => "User not found",
                ];
                return $this->response($data, 404);
            }
            return $response;
        }
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param PostRepository $postRepository
     * @param $postId
     * @param int $userId
     * @return JsonResponse
     * @Route("/users/{userId}/posts/{postId}", name="posts_put", methods={"PUT"})
     */
    public function updatePost(Request $request, EntityManagerInterface $entityManager,
                               PostRepository $postRepository, $postId, int $userId)
    {
        try {
            $post = $postRepository->find($postId);

            if (!$post) {
                $data = [
                    'status' => 404,
                    'errors' => "Post not found",
                ];
                return $this->response($data, 404);
            }

            $request = $this->transformJsonBody($request);

            if (!$request || !$request->get('name') || !$request->request->get('description')) {
                throw new \Exception();
            }

            if ($post->getUser()->getId() == $userId) {

                $post->setName($request->get('name'));
                $post->setDescription($request->get('description'));
                $entityManager->persist($post);
                $entityManager->flush();

                $data = [
                    'status' => 200,
                    'errors' => "Post updated successfully",
                ];
                return $this->response($data);
            } else {
                $data = [
                    'status' => 404,
                    'errors' => "User not found",
                ];
                return $this->response($data, 404);
            }

        } catch (\Exception $e) {
            $data = [
                'status' => 422,
                'errors' => "Data no valid",
            ];
            return $this->response($data, 422);
        }
    }

    /**
     * @param PostRepository $postRepository
     * @param EntityManagerInterface $entityManager
     * @param $postId
     * @param int $userId
     * @return JsonResponse
     * @Route("/users/{userId}/posts/{postId}", name="posts_delete", methods={"DELETE"})
     */
    public function deletePost(EntityManagerInterface $entityManager, PostRepository $postRepository,
                               int $postId, int $userId)
    {
        $post = $postRepository->find($postId);

        if (!$post) {
            $data = [
                'status' => 404,
                'errors' => "Post not found",
            ];
            return $this->response($data, 404);
        }
        if ($post->getUser()->getId() != $userId) {
            $data = [
                'status' => 404,
                'errors' => "User not found",
            ];
            return $this->response($data, 404);
        }

        $entityManager->remove($post);
        $entityManager->flush();
        $data = [
            'status' => 200,
            'errors' => "Post deleted successfully",
        ];
        return $this->response($data);
    }

    /**
     * Returns a JSON response
     *
     * @param array $data
     * @param $status
     * @param array $headers
     * @return JsonResponse
     */
    public function response($data, $status = 200, $headers = [])
    {
        return new JsonResponse($data, $status, $headers);
    }

    protected function transformJsonBody(\Symfony\Component\HttpFoundation\Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $request;
        }

        $request->request->replace($data);
        return $request;
    }
}