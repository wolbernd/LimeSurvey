<?php

namespace ls\tests;

use LimeSurvey\Models\Services\SaveQuestionData;
use PHPUnit\Framework\TestCase;
use Survey;
use LSHttpRequest;
use LSJsonException;

class SaveQuestionDataServiceTest extends TestCase
{
    public static function setupBeforeClass()
    {
        \Yii::import('application.helpers.common_helper', true);
    }

    /**
     */
    public function testEmptyQuestionData()
    {
        $surveyMock = $this->getMockBuilder(Survey::class)
            ->setMethods([
                'save',
                'attributes'
            ])
            ->getMock();
        $surveyMock->method('attributes')->willReturn([
            'sid'
        ]);

        $requestMock = $this->getMockBuilder(LSHttpRequest::class)
            ->getMock();

        $questionData = [];

        $service = new SaveQuestionData(
            $surveyMock,
            $requestMock,
            $questionData
        );

        $this->expectException(LSJsonException::class);
        $service->save(null);
    }
}
