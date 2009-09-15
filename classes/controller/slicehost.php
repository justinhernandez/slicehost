<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Slicehost extends Controller_Template_Demo
{
	// slice id
	private $slice_id = NULL;
	// api key can also be specified in the config, no need to pass everytime
	private $api_key = NULL;

	public function before()
	{
		// Slicehost::method($data = array(), $api_key);
		$this->b = Slicehost::backup(NULL, $this->api_key);
		$this->f = Slicehost::flavor(NULL, $this->api_key);
		$this->i = Slicehost::image(NULL, $this->api_key);
		$this->r = Slicehost::record(NULL, $this->api_key);
		$this->s = Slicehost::slice(NULL, $this->api_key);
		$this->z = Slicehost::zone(NULL, $this->api_key);

		parent::before();
	}

	// list backups, will find specific id if passed
	public function action_backups()
	{
		$this->d($this->b->find());
	}

	// list flavors, will find specific id if passed
	public function action_flavors()
	{
		$this->d($this->f->find());
	}

	// list images, will find specific id if passed
	public function action_images()
	{
		$data = $this->i->find();
		// use filter_data to filter _data in response data
		$filtered = Slicehost::filter_data($data);
		$this->d($filtered);
	}

	// list records, will find specific id if passed
	public function action_records()
	{
		$data = $this->r->find();
		$filtered = Slicehost::filter_data($data);
		$this->d($filtered);
	}

	// list slices, will find specific id if passed
	public function action_slices()
	{
		$this->d($this->s->find());
	}

	// list zones, will find specific id if passed
	public function action_zones()
	{
		$this->d($this->z->find());
	}

	// create new slice
	public function action_create_slice()
	{
		$slice = Slicehost::slice(
									array(
										'image_id' => 1,
										'flavor_id' => 1,
										'name' => 'example.com'
									),
									$this->api_key);
	
		$this->d($slice->save());
	}

	// option must be checked in slicehost manager control panel
	public function action_delete_slice()
	{
		$this->s->find($this->slice_id);
		$this->d($this->s->destroy());
	}

	// rename zone
	public function action_rename_slice()
	{
		$this->s->find($this->slice_id);
		$this->s->name = 'TestAPI';
		$this->d($this->s->save());
	}

	// option must be checked in slicehost manager control panel
	public function action_rebuild_slice()
	{
		$this->s->find($this->slice_id);
		// rebuild with slicehost image
		$this->s->put('rebuild', array('image_id' => 1));
		// rebuild from backup
		$this->s->put('rebuild', array('backup_id' => 1));
	}

	// reboot slice
	public function action_reboot_slice()
	{
		$this->s->find($this->slice_id);
		// soft reboot
		$this->s->put('reboot');
		// hard reboot
		$this->s->put('hard_reboot');
	}

	// create a new zone
	public function action_create_zone()
	{
		$zone = Slicehost::zone(
								array(
									'origin' => 'example.com',
									'ttl' => 86400
								),
								$this->api_key);
		$this->d($zone->save());
	}

	// delete zone
	public function action_delete_zone()
	{
		$zone_id = NULL;
		$this->z->find($zone_id);
		$this->z->destroy();
	}

	// create a new dns record, must specify zone id and ip address
	public function action_create_record()
	{
		$zone_id = NULL;
		$ip_address = NULL;
		$record = Slicehost::record(
									array(
										'record_type' => 'A',
										'zone_id' => $zone_id,
										'name' => 'www',
										'data' => $ip_address,
										'ttl' => '86400',
									),
									$this->api_key);
		$this->d($record->save());
	}

	// delete dns record
	public function action_delete_record()
	{
		$record_id = NULL;
		$this->z->find($record_id);
		$this->z->destroy();
	}

	// retrieve api information
	public function action_api_info()
	{
		$api = Slicehost::api($this->api_key);	
		$this->d($api);
	}
}