<?php

namespace App\Http\Controllers;

use App\Events\CommentCreated;
use App\Http\Requests\Document\View as DocumentViewRequest;
use App\Models\Annotation;
use App\Models\Doc as Document;
use App\Services;
use Event;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Response;

class CommentController extends Controller
{
    protected $annotationService;
    protected $commentService;

    public function __construct(Services\Annotations $annotationService, Services\Comments $commentService)
    {
        $this->annotationService = $annotationService;
        $this->commentService = $commentService;

        $this->middleware('auth')->except(['index', 'show']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(DocumentViewRequest $request, Document $document)
    {
        $excludeUserIds = [];
        if ($request->query('exclude_sponsors') && $request->query('exclude_sponsors') !== 'false') {
            $excludeUserIds = $document->sponsorIds;
        }

        $comments = new Collection();
        if ($request->query('parent_id')) {
            $commentsQuery = Annotation
                ::where('annotatable_type', Annotation::ANNOTATABLE_TYPE)
                ->where('annotatable_id', $request->query('parent_id'))
                ->where('annotation_type_type', Annotation::TYPE_COMMENT)
                ;
        } elseif ($request->query('all') && $request->query('all') !== 'false') {
            $commentsQuery = $document->allComments();
        } else {
            $commentsQuery = $document
                ->comments()
            ;
        }

        $commentsQuery
            ->whereNotIn('user_id', $excludeUserIds)
            ;

        if ($request->query('only_notes') && $request->query('only_notes') !== 'false') {
            $commentsQuery->onlyNotes();
        }

        if ($request->query('exclude_notes') && $request->query('exclude_notes') !== 'false') {
            $commentsQuery->notNotes();
        }

        $comments = $commentsQuery->get();

        // a little silly, we should probably support a more general
        // download=true param and a content type headers, but for now we'll
        // just do this because that's how it has been and the returned data
        // isn't exactly the same between the json and csv
        if ($request->query('download') === 'csv') {
            $csv = $this->commentService->toCsv($comments);
            $csv->output('comments.csv');
            return;
        } elseif ($request->expectsJson()) {
            $includeReplies = !$request->exists('include_replies') || $request->query('include_replies') && $request->query('include_replies') !== 'false';
            $results = $comments->map(function ($item) use ($includeReplies) {
                return $this->commentService->toAnnotatorArray($item, $includeReplies);
            });

            return Response::json($results);
        } else {
            // TODO: html view?
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // TODO
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(DocumentViewRequest $request, Document $document)
    {
        return $this->createComment($document, $request->user(), $request->all());
    }

    public function storeReply(DocumentViewRequest $request, Document $document, Annotation $comment)
    {
        $jsonResponse = $this->createComment($comment, $request->user(), $request->all());
        if ($request->expectsJson()) {
            return $jsonResponse;
        } else {
            return redirect()->route('documents.show', ['document' => $document->slug]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // TODO
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        // TODO
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // TODO
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // TODO
    }

    protected function createComment($target, $user, $data)
    {
        $newComment = $this->commentService->createFromAnnotatorArray($target, $user, $data);

        Event::fire(new CommentCreated($newComment, $target));

        return Response::json($this->commentService->toAnnotatorArray($newComment));
    }
}