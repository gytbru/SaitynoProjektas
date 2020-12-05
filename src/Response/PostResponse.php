<?php

namespace App\Response;

use App\Entity\Post;
use Symfony\Component\HttpFoundation\JsonResponse;

class PostResponse extends JsonResponse
{
    /**
     * @var Post[]
     */
    private $posts;

    public function __construct(array $posts)
    {
        $this->posts = $posts;

        if (!empty($this->posts)) {

            parent::__construct($this->serialize(), 200);

        } else {

            parent::__construct(
                $data = [
                    'status' => '404',
                    'errors' => 'Post not found',
                ],
                404);
        }
    }

    public function serialize()
    {
        $data = [];

        foreach ($this->posts as $post) {
            $data[] =
                [
                    'id' => $post->getId(),
                    'name' => $post->getName(),
                    'description' => $post->getDescription(),
                ];
        }
        return $data;
    }
}