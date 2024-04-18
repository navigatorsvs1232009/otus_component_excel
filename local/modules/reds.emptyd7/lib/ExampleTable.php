<?php

namespace Reds\Emptyd7;

class ExampleTable
{

    public function show()
    {
        $a = 'класс ExampleTable метод show';
        return $a;
    }

}

//
//use Bitrix\Main\Localization\Loc;
//use Bitrix\Main\ORM\Data\DataManager;
//use Bitrix\Main\ORM\Fields\DateField;
//use Bitrix\Main\ORM\Fields\IntegerField;
//use Bitrix\Main\ORM\Fields\StringField;
//use Bitrix\Main\ORM\Fields\Validators\LengthValidator;
//use Bitrix\Main\ORM\Fields\Validators\RegExpValidator;
//use Bitrix\Main\ORM\Fields\FieldError;
//use Bitrix\Main\ORM\Fields\Relations\Reference;
//use Bitrix\Main\ORM\Fields\Relations\OneToMany;
//use Bitrix\Main\ORM\Query\Join;
//use Reds\Emptyd7\AnotherTable;
//
//Loc::loadMessages(__FILE__);
//
///**
// * Class DataTable
// *
// * Fields:
// * <ul>
// * <li> id int mandatory
// * <li> client_id int mandatory
// * <li> ip_address string(20) mandatory
// * <li> date_time datetime mandatory
// * </ul>
// *
// * @package Bitrix\Data
// **/
//
//class ExampleTable extends DataManager
//{
//	/**
//	 * Returns DB table name for entity.
//	 *
//	 * @return string
//	 */
//	public static function getTableName()
//	{
//		return 'my_data';
//	}
//
//	/**
//	 * Returns entity map definition.
//	 *
//	 * @return array
//	 */
//	public static function getMap()
//	{
//		return [
//			new IntegerField(
//				'id',
//				[
//                    'primary' => true,
//                    'autocomplete' => true,
//                    'title' => Loc::getMessage('DATA_ENTITY_ID_FIELD'),
//				]
//			),
//			new IntegerField(
//				'client_id',
//				[
//                    'required' => true,
//					'title' => Loc::getMessage('DATA_ENTITY_CLIENT_ID_FIELD'),
//				]
//			),
//			new StringField(
//				'ip_address',
//				[
//                    'required' => true,
//					'validation' => function() {
//                        return array(
//                            new LengthValidator(null, 20),
//                            new RegExpValidator('/^(\d{1,3}).(\d{1,3}).(\d{1,3}).(\d{1,3})$/'),
//                            /*
//                            function ($value) {
//                                $clean = str_replace('-', '', $value);
//                                if (preg_match('/^(\d{1,3}).(\d{1,3}).(\d{1,3}).(\d{1,3})$/', $clean))
//                                {
//                                    return true;
//                                }
//                                else
//                                {
//                                    return 'Маска не совпадает';
//                                }
//                            },
//                            //*/
//                        );
//                    },
//					'title' => Loc::getMessage('DATA_ENTITY_IP_ADDRESS_FIELD'),
//				]
//			),
//			new DateField(
//				'date_time',
//				[
//                    'required' => true,
//					'title' => Loc::getMessage('DATA_ENTITY_DATE_TIME_FIELD'),
//				]
//			),
//
//
//
//        (new Reference(
//            'CLIENT',
//            \Bitrix\Crm\ContactTable::class,
//            Join::on('this.client_id', 'ref.ID')
//        ))
//            ->configureJoinType('inner'),
//
//
//            (new OneToMany(
//                'ANOTHER',
//                AnotherTable::class,
//                'EXAMPLE'
//            ))->configureJoinType('left')
//		];
//	}
//}
