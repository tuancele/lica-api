<?php

namespace App\Policies;

use App\User;
use App\Post;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;
    /**
     * Determine whether the user can viewAny the product.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Post  $post
     * @return mixed
     */
    public function viewAny(User $user)
    {
        return  false;
    }

    /**
     * Determine whether the user can view the product.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Post  $post
     * @return mixed
     */
    public function view(User $user, Post $post)
    {
        return  $user->can('post-edit') && $user->id === $post->user_id;
    }

    /**
     * Determine whether the user can create posts.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function create(User $user)
    {
        return $user->hasPermission('post-create');
    }
    /**
     * Determine whether the user can import posts.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function import(User $user)
    {
        return $user->hasVerifiedEmail();
    }

    /**
     * Determine whether the user can export posts.
     *
     * @param  \App\Models\User  $user
     * @return mixed
     */
    public function export(User $user)
    {
        return $user->hasVerifiedEmail();
    }

    /**
     * Determine whether the user can update the post.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Post  $post
     * @return mixed
     */
    public function update(User $user, Post $post)
    {
        return $user->can('post-edit') && $user->id == $post->user_id;
    }

    /**
     * Determine whether the user can delete the post.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Post  $post
     * @return mixed
     */
    public function delete(User $user, Post $post)
    {
        return $user->can('post-delete') && $user->id == $post->user_id;
    }

    /**
     * Determine whether the user can restore the post.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Post  $post
     * @return mixed
     */
    public function restore(User $user, Post $post)
    {
        return $user->id == $post->user_id;
    }

    /**
     * Determine whether the user can permanently delete the post.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Post  $post
     * @return mixed
     */
    public function forceDelete(User $user, Post $post)
    {
        return $user->id == $post->user_id;
    }
}