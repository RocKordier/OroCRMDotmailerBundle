<?php

namespace OroCRM\Bundle\DotmailerBundle\ImportExport\DataConverter;

class CampaignSummaryDataConverter extends AbstractDataConverter
{
    /**
     * {@inheritdoc}
     */
    protected function getHeaderConversionRules()
    {
        return [
            'datesent' => 'dateSent',
            'numuniqueopens' => 'numUniqueOpens',
            'numuniquetextopens' => 'numUniqueTextOpens',
            'numtotaluniqueopens' => 'numTotalUniqueOpens',
            'numopens' => 'numOpens',
            'numtextopens' => 'numTextOpens',
            'numtotalopens' => 'numTotalOpens',
            'numclicks' => 'numClicks',
            'numtextclicks' => 'numTextClicks',
            'numtotalclicks' => 'numTotalClicks',
            'numpageviews' => 'numPageViews',
            'numtotalpageviews' => 'numTotalPageViews',
            'numtextpageviews' => 'numTextPageViews',
            'numforwards' => 'numForwards',
            'numtextforwards' => 'numTextForwards',
            'numestimatedforwards' => 'numEstimatedForwards',
            'numtextestimatedforwards' => 'numTextEstimatedForwards',
            'numtotalestimatedforwards' => 'numTotalEstimatedForwards',
            'numreplies' => 'numReplies',
            'numtextreplies' => 'numTextReplies',
            'numtotalreplies' => 'numTotalReplies',
            'numhardbounces' => 'numHardBounces',
            'numtexthardbounces' => 'numTextHardBounces',
            'numtotalhardbounces' => 'numTotalHardBounces',
            'numsoftbounces' => 'numSoftBounces',
            'numtextsoftbounces' => 'numTextSoftBounces',
            'numtotalsoftbounces' => 'numTotalSoftBounces',
            'numunsubscribes' => 'numUnsubscribes',
            'numtextunsubscribes' => 'numTextUnsubscribes',
            'numtotalunsubscribes' => 'numTotalUnsubscribes',
            'numispcomplaints' => 'numIspComplaints',
            'numtextispcomplaints' => 'numTextIspComplaints',
            'numtotalispcomplaints' => 'numTotalIspComplaints',
            'nummailblocks' => 'numMailBlocks',
            'numtextmailblocks' => 'numTextMailBlocks',
            'numtotalmailblocks' => 'numTotalMailBlocks',
            'numsent' => 'numSent',
            'numtextsent' => 'numTextSent',
            'numtotalsent' => 'numTotalSent',
            'numrecipientsclicked' => 'numRecipientsClicked',
            'numdelivered' => 'numDelivered',
            'numtextdelivered' => 'numTextDelivered',
            'numtotaldelivered' => 'numTotalDelivered',
            'percentagedelivered' => 'percentageDelivered',
            'percentageuniqueopens' => 'percentageUniqueOpens',
            'percentageopens' => 'percentageOpens',
            'percentageunsubscribes' => 'percentageUnsubscribes',
            'percentagereplies' => 'percentageReplies',
            'percentagehardbounces' => 'percentageHardBounces',
            'percentagesoftbounces' => 'percentageSoftBounces',
            'percentageusersclicked' => 'percentageUsersClicked',
            'percentageclickstoopens' => 'percentageClicksToOpens',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getBackendHeader()
    {
        return
            [
                'datesent',
                'numuniqueopens',
                'numuniquetextopens',
                'numtotaluniqueopens',
                'numopens',
                'numtextopens',
                'numtotalopens',
                'numclicks',
                'numtextclicks',
                'numtotalclicks',
                'numpageviews',
                'numtotalpageviews',
                'numtextpageviews',
                'numforwards',
                'numtextforwards',
                'numestimatedforwards',
                'numtextestimatedforwards',
                'numtotalestimatedforwards',
                'numreplies',
                'numtextreplies',
                'numtotalreplies',
                'numhardbounces',
                'numtexthardbounces',
                'numtotalhardbounces',
                'numsoftbounces',
                'numtextsoftbounces',
                'numtotalsoftbounces',
                'numunsubscribes',
                'numtextunsubscribes',
                'numtotalunsubscribes',
                'numispcomplaints',
                'numtextispcomplaints',
                'numtotalispcomplaints',
                'nummailblocks',
                'numtextmailblocks',
                'numtotalmailblocks',
                'numsent',
                'numtextsent',
                'numtotalsent',
                'numrecipientsclicked',
                'numdelivered',
                'numtextdelivered',
                'numtotaldelivered',
                'percentagedelivered',
                'percentageuniqueopens',
                'percentageopens',
                'percentageunsubscribes',
                'percentagereplies',
                'percentagehardbounces',
                'percentagesoftbounces',
                'percentageusersclicked',
                'percentageclickstoopens',
            ];
    }
}
