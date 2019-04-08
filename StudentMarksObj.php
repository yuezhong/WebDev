<?php

class StudentMarksObj
{
        private $assessment;
        private $marks;
        private $comments;
        private $studentId;
        private $maxmarks;

		// May not need $assessment since we're just creating and overwriting...
        public function __construct($assessment, $id)
        {
               $this->assessment = $assessment;
               $this->studentId = $id;
               $this->comments = "";
               
                switch($assessment)
                {
					case 1:
					case 2:
					case 4:
					$this->maxmarks = 3;
					break;
					case 3:
					case '3a':
					case 5:
					$this->maxmarks = 4;
					break;
					case 6:
					$this->maxmarks = 2;
					break;
					case 7:
					$this->maxmarks = 5;
					break;
				 default:
					$this->maxmarks = 3;
                }
        }

		public function getCA()
		{
			return $this->assessment;
		}
		
		public function setMaxMarks($marks)
        {
                $this->maxmarks = $marks;
        }
		
        public function getMaxMarks($ca)
        {
			switch($ca)
			{
				case 1:
				case 2:
				case 4:
				$this->maxmarks = 3;
				break;
				case 3:
				case '3a':
				case 5:
				$this->maxmarks = 4;
				break;
				case 6:
				$this->maxmarks = 2;
				break;
				case 7:
				$this->maxmarks = 5;
				break;
			 default:
				$this->maxmarks = 3;
			}
            return $this->maxmarks;
        }

        public function getMarks()
        {
            return $this->marks;
        }

        public function setMarks($marks)
        {
            $this->marks = $marks;
        }

        public function setStudentId($studentid)
        {
            $this->studentId = $studentid;
        }

        public function getStudentId()
        {
            return $this->studentId;
        }

        public function getComments()
        {
            return $this->comments;
        }

        public function setComments($comments)
        {
            $this->comments = $comments;
        }

} // End StudentMarksObj

?>
