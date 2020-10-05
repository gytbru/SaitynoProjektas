<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CommentController
 * @package App\Controller
 * @Route("/api", name="comment_api")
 */
class CommentController extends AbstractController
{
    /**
     * @param UserRepository $userRepository
     * @param int $userId
     * @param int $postId
     * @return JsonResponse
     * @Route("/users/{userId}/posts/{postId}/comments", name="comments", methods={"GET"})
     */
    public function getComments(UserRepository $userRepository, int $userId, int $postId)
    {
        $response = new JsonResponse();
        $comments = [];
        $data = [];
        $posts = [];

        $user = $userRepository->findOneBy(['id' => $userId]);
        if (!empty($user)) {
            $posts = $user->getPosts();
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

        foreach ($posts as $post) {

            if ($post->getId() == $postId) {
                $comments = $post->getComments();
                break;
            }
        }

        foreach ($comments as $comment) {

            $data[] =
                [
                    'id' => $comment->getId(),
                    'subject' => $comment->getSubject(),
                    'body' => $comment->getBody(),
                ];
        }
        if (empty($data)) {
            $response->setData(
                [
                    'status' => '404',
                    'errors' => 'Comment not found',
                ]
            );
            $response->setStatusCode(404);
            return $response;
        }

        return $this->response($data, 200);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param PostRepository $postRepository
     * @param int $userId
     * @param int $postId
     * @return JsonResponse
     * @throws \Exception
     * @Route("/users/{userId}/posts/{postId}/comments", name="comments_add", methods={"POST"})
     */
    public function addComment(Request $request, EntityManagerInterface $entityManager,
                               postRepository $postRepository, int $userId, int $postId)
    {

        try {
            $request = $this->transformJsonBody($request);

            if (!$request || !$request->get('subject') || !$request->request->get('body')) {
                throw new \Exception();
            }

            $post = $postRepository->findOneBy(['id' => $postId]);
            if (!empty($post)) {
                if ($post->getUser()->getId() == $userId) {

                    $comment = new Comment();
                    $comment->setSubject($request->get('subject'));
                    $comment->setBody($request->get('body'));
                    $comment->setPost($post);
                    $entityManager->persist($comment);
                    $entityManager->flush();

                    $data = [
                        'status' => 200,
                        'success' => "Comment added successfully",
                    ];
                    return $this->response($data);
                } else {
                    $data = [
                        'status' => 404,
                        'errors' => "User not found",
                    ];
                    return $this->response($data, 404);
                }
            } else {
                $data = [
                    'status' => 404,
                    'errors' => "Post not found",
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
     * @param CommentRepository $commentRepository
     * @param PostRepository $postRepository
     * @param $commentId
     * @param $userId
     * @param $postId
     * @return JsonResponse
     * @Route("/users/{userId}/posts/{postId}/comments/{commentId}", name="comments_get", methods={"GET"})
     */
    public function getComment(CommentRepository $commentRepository, PostRepository $postRepository,
                               $commentId, $userId, $postId)
    {
        $post = $postRepository->findOneBy(['id' => $postId]);
        if (!empty($post)) {
            if ($post->getUser()->getId() == $userId) {
                $comment = $commentRepository->find($commentId);
            } else {
                $data = [
                    'status' => 404,
                    'errors' => "User not found",
                ];
                return $this->response($data, 404);
            }
        } else {
            $data = [
                'status' => 404,
                'errors' => "Post not found",
            ];
            return $this->response($data, 404);
        }

        if (!$comment) {
            $data = [
                'status' => 404,
                'errors' => "Comment not found",
            ];
            return $this->response($data, 404);
        }
        return $this->response($comment);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     * @param $commentId
     * @param $userId
     * @param $postId
     * @return JsonResponse
     * @Route("/users/{userId}/posts/{postId}/comments/{commentId}", name="comments_put", methods={"PUT"})
     */
    public function updateComment(Request $request, EntityManagerInterface $entityManager,
                                  UserRepository $userRepository, $commentId, $userId, $postId)
    {
        try {
            $user = $userRepository->find($userId);
            $found = false;

            if (!$user) {
                $data = [
                    'status' => 404,
                    'errors' => "User not found",
                ];
                return $this->response($data, 404);
            } else {
                $posts = $user->getPosts();
                if (!empty($posts)) {
                    foreach ($posts as $post) {
                        if ($post->getId() == $postId) {

                            $comments = $post->getComments();
                            if (!empty($comments)) {
                                foreach ($comments as $comment) {
                                    if ($comment->getId() == $commentId) {
                                        $request = $this->transformJsonBody($request);

                                        if (!$request || !$request->get('subject') || !$request->request->get('body')) {
                                            throw new \Exception();
                                        }
                                        $comment->setSubject($request->get('subject'));
                                        $comment->setBody($request->get('body'));
                                        $entityManager->persist($comment);
                                        $entityManager->flush();

                                        $data = [
                                            'status' => 200,
                                            'errors' => "Comment updated successfully",
                                        ];
                                        return $this->response($data);
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $data = [
                        'status' => 404,
                        'errors' => "Post not found",
                    ];
                    return $this->response($data, 404);
                }
            }

            if (!$found) {
                $data = [
                    'status' => 404,
                    'errors' => "Comment not found",
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
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     * @param $commentId
     * @param $userId
     * @param $postId
     * @return JsonResponse
     * @Route("/users/{userId}/posts/{postId}/comments/{commentId}", name="comments_delete", methods={"DELETE"})
     */
    public function deleteComment(EntityManagerInterface $entityManager,
                                  UserRepository $userRepository,
                                  $commentId,
                                  $userId,
                                  $postId
    )
    {
        $found = false;
        $user = $userRepository->find($userId);

        if (!$user) {
            $data = [
                'status' => 404,
                'errors' => "Comment not found",
            ];
            return $this->response($data, 404);
        } else {
            $posts = $user->getPosts();
            if (!empty($posts)) {
                foreach ($posts as $post) {
                    if ($post->getId() == $postId) {
                        $comments = $post->getComments();
                        if (!empty($comments)) {
                            foreach ($comments as $comment) {
                                if ($comment->getId() == $commentId) {
                                    $entityManager->remove($comment);
                                    $entityManager->flush();
                                    $data = [
                                        'status' => 200,
                                        'errors' => "Comment deleted successfully",
                                    ];
                                    return $this->response($data);
                                }
                            }
                        }
                    }
                }
            }
        }
        if (!$found) {
            $data = [
                'status' => 404,
                'errors' => "Comment not found",
            ];
            return $this->response($data, 404);
        }
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