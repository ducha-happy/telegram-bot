<?php

namespace Ducha\TelegramBot\Poll;

class Poll
{
    protected $id;

    /**
     * Name of the poll
     * @var string
     */
    protected $name;

    /**
     * Questions of the poll
     * @var array
     */
    protected $questions;

    /**
     * Keeps whom the poll is owned
     * @var int
     */
    protected $user_id;

    public function __construct($id, $user_id, $name = null, $questions = array())
    {
        $this->validate($questions);

        $this->id = $id;
        $this->user_id = $user_id;
        $this->name = $name;
        $this->questions = $questions;
    }

    /**
     * @param int $id
     * @return string
     */
    public static function getStorageKey($id)
    {
        return sprintf('telegram.poll.%s', $id);
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getQuestions()
    {
        return $this->questions;
    }

    protected function validate(array $questions)
    {
        foreach ($questions as $question){
            if (!$question instanceof PollQuestion){
                throw new \InvalidArgumentException(sprintf('Bad argument question must be instanceof %s but was given %s ', PollQuestion::class, gettype($question)));
            }
        }
    }

    public function setQuestions(array $questions)
    {
        $this->validate($questions);

        return $this->questions = $questions;
    }

    /**
     * @param PollQuestion $question
     */
    public function addQuestion(PollQuestion $question)
    {
        $this->questions[] = $question;
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        $title = $this->getId() . ' ' . $this->getName();
        $delimiter = '';
        for ($i = 0; $i < mb_strlen($title); $i++){
            $delimiter .= '-';
        }

        $temp = array(
            $title,
            $delimiter
        );

        foreach ($this->getQuestions() as $question){
            $temp[] = $question->getTitle();
            $replies = array();
            foreach ($question->getReplies() as $reply) {
                $replies[] = $reply;
            }
            $temp[] = implode(", ", $replies);
            $temp[] = '-';
        }
        unset($temp[ count($temp)-1 ]);

        $temp[] = $delimiter;
        $temp[] = '';

        return implode("\n", $temp);
    }
}