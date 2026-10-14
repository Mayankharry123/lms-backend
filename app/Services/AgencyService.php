<?php
/**
 * AgencyService
 * -----------------------------------------
 * This service class provides methods to manage agencies, including
 * creating, updating, retrieving, and deleting agency records. It also
 * handles relationships between agencies and brands.
 *
 * @package App\Services
 * @author Achal Sharma
 * @version 1.0.0
 * @since 2026-05-05
 */

namespace App\Services;

use App\Contracts\Repositories\AgencyRepositoryInterface;
use App\Models\Agency;
use App\Models\BrandAgencyRelationship;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use App\Services\ResponseService;
use DomainException;

class AgencyService
{
    /**
     * @var AgencyRepositoryInterface
     */
    protected $repo;

    /**
     * @var ResponseService
     */
    protected ResponseService $responseService;

    /**
     * AgencyService constructor.
     *
     * @param ResponseService $responseService
     * @param AgencyRepositoryInterface $repo
     */
    public function __construct(ResponseService $responseService, AgencyRepositoryInterface $repo) { 
        $this->repo = $repo;
        $this->responseService = $responseService; 
    }
    
    /**
     * Get all agencies.
     *
     * @return mixed
     */
    public function getAll() { 
        return $this->repo->getAllAgency(); 
    }
    
    /**
     * Get agency by ID.
     *
     * @param int|string $id
     * @return mixed
     */
    public function getById($id) {
        return $this->repo->getAgencyById($id);
    }
    
    /**
     * Delete agency by ID.
     *
     * @param int|string $id
     * @return mixed
     */
    public function delete($id) { 
        return $this->repo->deleteAgency($id); 
    }

    /**
     * Create agencies and their brand relationships.
     *
     * @param array $data
     * @return mixed
     */
    public function create(array $data) {
        try {
            DB::beginTransaction();
    
            $agency = null;
            $is_parent = null;
    
            if (!empty($data['name']) && !empty($data['type'])) {
                $count = count($data['name']);
    
                for ($i = 0; $i < $count; $i++) {
                    if (!empty($data['name'][$i]) && !empty($data['type'][$i])) {
                        
                        // Check if agency name already exists (excluding soft-deleted)
                        if (Agency::where('name', $data['name'][$i])->whereNull('deleted_at')->exists()) {
                            throw new \DomainException('Agency name "' . $data['name'][$i] . '" already exists. Agency name must be unique.');
                        }
    
                        $agencyData = [
                            'name'              => $data['name'][$i],
                            'agency_type'    => $data['type'][$i],
                            'slug'              => Str::slug($data['name'][$i]),
                            'is_parent'         => $is_parent,
                            'status'            => 1,
                        ];

                        $clients = !empty($data['client']) && !empty($data['client'][$i]) ? $data['client'][$i] : [];
    
                        $agency = $this->repo->createAgency($agencyData);
    
                        // Set the first created agency as parent
                        if ($i == 0) {
                            $is_parent = $agency->id;
                        }
                         
                        if(!empty($clients) && is_array($clients)){
                            // Get Direct agency ID
                            $directAgencyId = $this->getOrCreateDirectAgency();
                            
                            foreach($clients as $client){
                                // Remove Direct agency from this brand if it exists
                                BrandAgencyRelationship::where('brand_id', $client)
                                    ->where('agency_id', $directAgencyId)
                                    ->delete();
                                
                                // Assign this agency to the brand
                                $agencyClients = new BrandAgencyRelationship(); 
                                $agencyClients->agency_id = $agency->id;
                                $agencyClients->brand_id = $client;
                                $agencyClients->save();
                            }
                        }
                    }
                }
            }
            
            DB::commit();
    
            return $agency;
        } catch (ValidationException $e) {
            DB::rollBack();
            return $this->responseService->validationError($e->errors(), 'Registration validation failed');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->responseService->serverError('Registration failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Update an agency and its brand relationships.
     *
     * @param int $id
     * @param array $data
     * @return Agency
     * @throws ValidationException
     * @throws \Exception
     */
    public function update(int $id, array $data) {
        try {
            DB::beginTransaction();
    
            $agency = Agency::findOrFail($id);
    
            if (!empty($data['name']) && is_array($data['name'])) {
                $name = $data['name'][0] ?? null;
                
                if ($name) {
                    // Check if agency name already exists (excluding current agency)
                    if (Agency::where('name', $name)
                        ->where('id', '!=', $id)
                        ->whereNull('deleted_at')
                        ->exists()) {
                        throw new \DomainException('Agency name "' . $name . '" already exists. Agency name must be unique.');
                    }
                    
                    $agency->name = $name;
                    $agency->slug = Str::slug($name);
                }
            }
    
            if (!empty($data['type']) && is_array($data['type'])) {
                $type = $data['type'][0] ?? null;
                if ($type) {
                    $agency->agency_type = $type;
                }
            }
    
            $agency->save();
    
            // Handle client/brand relationships
            if (!empty($data['client']) && is_array($data['client'])) {
                $clients = $data['client'][0] ?? [];
                
                if (is_array($clients)) {
                    // Remove existing relationships
                    BrandAgencyRelationship::where('agency_id', $id)->delete();
                    
                    // Add new relationships
                    foreach ($clients as $client) {
                        $directAgencyId = $this->getOrCreateDirectAgency();
                        
                        // Remove Direct agency from this brand if it exists
                        BrandAgencyRelationship::where('brand_id', $client)
                            ->where('agency_id', $directAgencyId)
                            ->delete();
                        
                        // Assign this agency to the brand
                        BrandAgencyRelationship::create([
                            'agency_id' => $agency->id,
                            'brand_id' => $client,
                        ]);
                    }
                }
            }
    
            DB::commit();
            return $agency;
            
        } catch (ValidationException $e) {
            DB::rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create a basic agency.
     *
     * @param array $data
     * @return Agency
     * @throws DomainException
     */
    public function createBasicAgency(array $data): Agency
    {
        try {
            if (empty($data['name'])) {
                throw new DomainException('Agency name is required.');
            }

            if (Agency::where('name', $data['name'])->whereNull('deleted_at')->exists()) {
                throw new DomainException('Agency name must be unique. This agency name already exists.');
            }

            $baseSlug = Str::slug($data['name']);
            if ($baseSlug === '') {
                $baseSlug = 'agency';
            }

            $slug = $baseSlug;
            $counter = 1;
            while (Agency::where('slug', $slug)->whereNull('deleted_at')->exists()) {
                $slug = $baseSlug . '-' . $counter;
                $counter++;
            }

            $agencyData = [
                'name' => $data['name'],
                'slug' => $slug,
                'status' => $data['status'] ?? '1',
                'agency_type' => $data['agency_type'] ?? null,
                'is_parent' => $data['is_parent'] ?? null,
            ];

            return $this->repo->createAgency($agencyData);
        } catch (QueryException $e) {
            $isDuplicateKey = ($e->getCode() === '23000')
                && isset($e->errorInfo[1])
                && (int) $e->errorInfo[1] === 1062;

            if ($isDuplicateKey) {
                throw new DomainException('Agency name must be unique. This agency name already exists.');
            }

            throw new DomainException('Unable to create agency: ' . $e->getMessage());
        } catch (DomainException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new DomainException('Unable to create agency: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate slug from name.
     *
     * @param string $name
     * @param mixed $excludeId
     * @return string
     */
    private function createUniqueSlug(string $name, $excludeId = null): string {
        return Str::slug($name);
    }

    /**
     * Get or create the Direct agency.
     *
     * @return int
     */
    private function getOrCreateDirectAgency(): int
    {
        $directAgency = Agency::withTrashed()
            ->whereRaw('LOWER(name) = ?', ['direct'])
            ->first();

        if (!$directAgency) {
            $directAgency = Agency::create([
                'name' => 'Direct',
                'slug' => 'direct',
                'status' => '1',
                'agency_type' => 1, // Assuming default agency type ID is 1
            ]);
        } elseif ($directAgency->trashed()) {
            $directAgency->restore();
        }

        return $directAgency->id;
    }

}