<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\TopicNode;

class TopicNodeController extends Controller
{
    /**
     * Topic Node list page
     */
    public function index()
    {
        $rootNodes = TopicNode::roots()->get();

        return view('admin.topic.node', compact('rootNodes'));
    }

    /**
     * Topic Node to left
     */
    public function toLeft(Request $request)
    {
        $this->validate($request, [
            'id'        =>      'required|integer',
        ]);

        $node = TopicNode::findOrFail($request->id);
        $node->moveLeft();

        flash('操作成功')->success();
        return back();
    }

    /**
     * Topic Node to right
     */
    public function toRight(Request $request)
    {
        $this->validate($request, [
            'id'        =>      'required|integer',
        ]);

        $node = TopicNode::findOrFail($request->id);
        $node->moveRight();

        flash('操作成功')->success();
        return back();
    }

    /**
     * Topic Node store
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'parent_id'     =>      'required|integer',
            'name'          =>      'required|string',
        ]);

        $parentNode = TopicNode::find($request->parent_id);
        $topicNode = TopicNode::create([
            'name'  =>  $request->name,
            'description'  =>  $request->description
        ]);

        if ($parentNode) {
            $topicNode->makeChildOf($parentNode);
        } else {
            $topicNode->makeRoot();
        }

        flash('操作成功')->success();
        return back();
    }

    /**
     * Topic Node update
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'id'        =>      'required|integer',
            'name'      =>      'required|string',
        ]);

        $node = TopicNode::findOrFail($request->id);

        if ($node->isRoot()) {
            if ($request->parent_id == 0) {
                $node->update($request->only(['name', 'description']));
            } else {
                if ($node->children->count()) {
                    flash('有子节点的节点不能设为子节点')->error();
                    return back();
                } else {
                    $node->update($request->only(['parent_id', 'name', 'description']));
                    $parentNode = TopicNode::findOrFail($request->parent_id);
                    $node->makeChildOf($parentNode);
                }
            }
        } else {
            if ($request->parent_id == 0) {
                $node->update($request->only(['name', 'description']));
                $node->update(['parent_id' => null]);
                $node->makeRoot();
            } else {
                $node->update($request->only(['parent_id', 'name', 'description']));
            }
        }

        flash('操作成功')->success();
        return back();
    }

    /**
     * Topic Node destroy
     */
    public function destroy(Request $request)
    {
        $this->validate($request, [
            'id'        =>      'required|integer',
        ]);

        $node = TopicNode::findOrFail($request->id);

        if ($node->children->count()) {
            flash('操作失败, 请先删除该节点下的所有子节点')->error();
            return back();
        } else {
            if ($node->topics()->count()) {
                flash('操作失败, 请先删除该节点下的所有话题')->error();
                return back();
            }
        }

        $node->delete();

        flash('操作成功');
        return back();
    }
}
