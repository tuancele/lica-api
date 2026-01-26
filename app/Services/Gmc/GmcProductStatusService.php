<?php

declare(strict_types=1);
namespace App\Services\Gmc;

use Google\Service\ShoppingContent;
use Google\Service\ShoppingContent\ProductStatus;
use Illuminate\Support\Facades\Log;

/**
 * Service to fetch product status from Google Merchant Center
 */
class GmcProductStatusService
{
    public function __construct(
        private GmcClientFactory $clientFactory
    ) {}

    /**
     * Get product status from Google Merchant Center
     * 
     * @param string $offerId The offer ID (SKU or variant ID)
     * @return array{status:string|null, issues:array, destination_statuses:array}|null
     */
    public function getProductStatus(string $offerId): ?array
    {
        $merchantId = (string) config('gmc.merchant_id', '');
        if ($merchantId === '') {
            Log::warning('[GMC] Cannot fetch product status: merchant_id is missing');
            return null;
        }

        try {
            $service = $this->clientFactory->makeContentService();
            
            // Use productstatuses.get to get status
            $productStatus = $service->productstatuses->get($merchantId, $offerId);
            
            if (!$productStatus) {
                return null;
            }

            $status = $productStatus->getDataQualityIssues() ? 'disapproved' : 
                     ($productStatus->getItemLevelIssues() ? 'pending' : 'approved');

            $issues = [];
            
            // Collect data quality issues
            if ($productStatus->getDataQualityIssues()) {
                foreach ($productStatus->getDataQualityIssues() as $issue) {
                    $issues[] = [
                        'severity' => $issue->getSeverity() ?? 'unknown',
                        'id' => $issue->getId() ?? '',
                        'timestamp' => $issue->getTimestamp() ?? '',
                        'value' => $issue->getValue() ?? '',
                        'detail' => $issue->getDetail() ?? '',
                        'location' => $issue->getLocation() ?? '',
                    ];
                }
            }

            // Collect item level issues
            if ($productStatus->getItemLevelIssues()) {
                foreach ($productStatus->getItemLevelIssues() as $issue) {
                    $issues[] = [
                        'severity' => $issue->getSeverity() ?? 'unknown',
                        'code' => $issue->getCode() ?? '',
                        'attribute' => $issue->getAttribute() ?? '',
                        'description' => $issue->getDescription() ?? '',
                        'detail' => $issue->getDetail() ?? '',
                        'resolution' => $issue->getResolution() ?? '',
                        'destination' => $issue->getDestination() ?? '',
                    ];
                }
            }

            // Get destination statuses
            $destinationStatuses = [];
            if ($productStatus->getDestinationStatuses()) {
                foreach ($productStatus->getDestinationStatuses() as $destStatus) {
                    $destinationStatuses[] = [
                        'destination' => $destStatus->getDestination() ?? '',
                        'status' => $destStatus->getStatus() ?? '',
                        'pending_count' => $destStatus->getPendingCount() ?? 0,
                        'approved_count' => $destStatus->getApprovedCount() ?? 0,
                        'disapproved_count' => $destStatus->getDisapprovedCount() ?? 0,
                    ];
                }
            }

            return [
                'status' => $status,
                'issues' => $issues,
                'destination_statuses' => $destinationStatuses,
            ];
        } catch (\Throwable $e) {
            Log::error('[GMC] Failed to fetch product status', [
                'offer_id' => $offerId,
                'merchant_id' => $merchantId,
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);
            
            // If product not found, return null
            if ($e->getCode() === 404) {
                return null;
            }
            
            return [
                'status' => 'error',
                'issues' => [['description' => $e->getMessage()]],
                'destination_statuses' => [],
            ];
        }
    }

    /**
     * Get multiple product statuses in batch
     * 
     * @param array<string> $offerIds Array of offer IDs
     * @return array<string, array> Map of offerId => status data
     */
    public function getBatchProductStatuses(array $offerIds): array
    {
        $results = [];
        foreach ($offerIds as $offerId) {
            $results[$offerId] = $this->getProductStatus($offerId);
        }
        return $results;
    }
}

